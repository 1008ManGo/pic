package main

import (
	"bytes"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"net/http"
	"os"
	"runtime"
	"sync"
	"sync/atomic"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/mem"
)

type Config struct {
	APIURL      string
	Token       string
	TotalSMS    int
	TPS         int
	PhonePrefix string
	Content     string
	SenderID    string
}

type Result struct {
	TotalSent      int64          `json:"total_sent"`
	TotalSuccess   int64          `json:"total_success"`
	TotalFailed    int64          `json:"total_failed"`
	RequestsPerSec float64        `json:"requests_per_sec"`
	AvgLatencyMs   float64        `json:"avg_latency_ms"`
	MinLatencyMs   float64        `json:"min_latency_ms"`
	MaxLatencyMs   float64        `json:"max_latency_ms"`
	P95LatencyMs   float64        `json:"p95_latency_ms"`
	P99LatencyMs   float64        `json:"p99_latency_ms"`
	Duration       time.Duration  `json:"duration"`
	StartTime      time.Time      `json:"start_time"`
	EndTime        time.Time      `json:"end_time"`
	Config         Config         `json:"config"`
	ErrorTypes     map[string]int `json:"error_types"`
}

type SystemMetrics struct {
	Timestamp     time.Time `json:"timestamp"`
	CPUPercent    float64   `json:"cpu_percent"`
	MemoryPercent float64   `json:"memory_percent"`
	MemoryUsedMB  uint64    `json:"memory_used_mb"`
	MemoryTotalMB uint64    `json:"memory_total_mb"`
	Goroutines    int       `json:"goroutines"`
}

type TestRun struct {
	TestName    string          `json:"test_name"`
	IdleMetrics SystemMetrics   `json:"idle_metrics"`
	LoadMetrics []SystemMetrics `json:"load_metrics"`
	Result      Result          `json:"result"`
}

var (
	config      Config
	testRuns    []TestRun
	latencies   []float64
	latenciesMu sync.Mutex
)

func init() {
	flag.StringVar(&config.APIURL, "api", "http://localhost:8080/api/sms/send", "SMS API URL")
	flag.StringVar(&config.Token, "token", "", "Auth token")
	flag.IntVar(&config.TotalSMS, "count", 1000, "Total SMS to send")
	flag.IntVar(&config.TPS, "tps", 50, "Target TPS")
	flag.StringVar(&config.PhonePrefix, "prefix", "+447", "Phone number prefix")
	flag.StringVar(&config.Content, "content", "压力测试消息", "SMS content")
	flag.StringVar(&config.SenderID, "sender", "SYSTEST", "Sender ID")
}

func main() {
	flag.Parse()

	if config.Token == "" {
		fmt.Println("Error: -token is required")
		flag.Usage()
		os.Exit(1)
	}

	fmt.Println("========================================")
	fmt.Println("        SMS Gateway Stress Test         ")
	fmt.Println("========================================")
	fmt.Println()
	fmt.Printf("Configuration:\n")
	fmt.Printf("  API URL:      %s\n", config.APIURL)
	fmt.Printf("  Total SMS:    %d\n", config.TotalSMS)
	fmt.Printf("  Target TPS:   %d\n", config.TPS)
	fmt.Printf("  Phone Prefix: %s\n", config.PhonePrefix)
	fmt.Printf("  Content:      %s\n", config.Content)
	fmt.Println()

	testRun := TestRun{
		TestName: fmt.Sprintf("%dSMS_%dTPS", config.TotalSMS, config.TPS),
	}

	fmt.Println("[1/4] Collecting idle system metrics...")
	idleMetrics := collectSystemMetrics()
	printMetrics(idleMetrics)
	testRun.IdleMetrics = idleMetrics

	fmt.Println()
	fmt.Println("[2/4] Starting stress test...")

	latencies = make([]float64, 0, config.TotalSMS)

	result := runStressTest()
	testRun.Result = result

	fmt.Println()
	fmt.Println("[3/4] Collecting load system metrics...")
	testRun.LoadMetrics = collectLoadMetrics(5)

	fmt.Println()
	fmt.Println("[4/4] Generating report...")

	saveReport(testRun)

	printResult(result)

	fmt.Println()
	fmt.Println("========================================")
	fmt.Printf("Report saved to: stress_test_report.json\n")
	fmt.Println("========================================")
}

func runStressTest() Result {
	var totalSent int64
	var totalSuccess int64
	var totalFailed int64
	var totalLatency float64
	var minLatency float64 = 999999
	var maxLatency float64
	errorTypes := make(map[string]int)

	startTime := time.Now()

	interval := time.Second / time.Duration(config.TPS)
	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	var wg sync.WaitGroup
	sem := make(chan struct{}, config.TPS*2)

	for i := 0; i < config.TotalSMS; i++ {
		<-ticker.C

		atomic.AddInt64(&totalSent, 1)
		wg.Add(1)
		sem <- struct{}{}

		go func(seq int) {
			defer wg.Done()
			defer func() { <-sem }()

			phone := fmt.Sprintf("%s%d", config.PhonePrefix, 447000000+seq%10000000)
			latency := sendSMS(phone)

			latenciesMu.Lock()
			latencies = append(latencies, latency)
			if latency < minLatency {
				minLatency = latency
			}
			if latency > maxLatency {
				maxLatency = latency
			}
			latenciesMu.Unlock()

			if latency > 0 {
				atomic.AddInt64(&totalSuccess, 1)
			} else {
				atomic.AddInt64(&totalFailed, 1)
			}
			atomic.AddFloat64(&totalLatency, latency)
		}(i)

		if (i+1)%1000 == 0 {
			fmt.Printf("\r  Progress: %d / %d (%.1f%%)", i+1, config.TotalSMS, float64(i+1)/float64(config.TotalSMS)*100)
		}
	}

	wg.Wait()
	endTime := time.Now()
	duration := endTime.Sub(startTime)

	success := atomic.LoadInt64(&totalSuccess)
	failed := atomic.LoadInt64(&totalFailed)
	avgLatency := totalLatency / float64(success+failed)
	if success+failed == 0 {
		avgLatency = 0
	}

	sortLatencies()
	p95 := percentile(95)
	p99 := percentile(99)

	return Result{
		TotalSent:      totalSent,
		TotalSuccess:   success,
		TotalFailed:    failed,
		RequestsPerSec: float64(totalSent) / duration.Seconds(),
		AvgLatencyMs:   avgLatency,
		MinLatencyMs:   minLatency,
		MaxLatencyMs:   maxLatency,
		P95LatencyMs:   p95,
		P99LatencyMs:   p99,
		Duration:       duration,
		StartTime:      startTime,
		EndTime:        endTime,
		Config:         config,
		ErrorTypes:     errorTypes,
	}
}

func sendSMS(phone string) float64 {
	start := time.Now()

	payload := map[string]interface{}{
		"phones":  []string{phone},
		"content": config.Content,
	}
	if config.SenderID != "" {
		payload["sender_id"] = config.SenderID
	}

	body, _ := json.Marshal(payload)

	req, _ := http.NewRequest("POST", config.APIURL, bytes.NewBuffer(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+config.Token)

	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return 0
	}
	defer resp.Body.Close()

	io.ReadAll(resp.Body)

	latency := time.Since(start).Seconds() * 1000
	return latency
}

func collectSystemMetrics() SystemMetrics {
	var m SystemMetrics
	m.Timestamp = time.Now()

	cpuPercent, _ := cpu.Percent(time.Second, false)
	if len(cpuPercent) > 0 {
		m.CPUPercent = cpuPercent[0]
	}

	memInfo, _ := mem.VirtualMemory()
	if memInfo != nil {
		m.MemoryPercent = memInfo.UsedPercent
		m.MemoryUsedMB = memInfo.Used / 1024 / 1024
		m.MemoryTotalMB = memInfo.Total / 1024 / 1024
	}

	m.Goroutines = runtime.NumGoroutine()

	return m
}

func collectLoadMetrics(count int) []SystemMetrics {
	metrics := make([]SystemMetrics, count)
	for i := 0; i < count; i++ {
		metrics[i] = collectSystemMetrics()
		time.Sleep(time.Second)
	}
	return metrics
}

func sortLatencies() {
	latenciesMu.Lock()
	defer latenciesMu.Unlock()
	for i := 0; i < len(latencies)-1; i++ {
		for j := i + 1; j < len(latencies); j++ {
			if latencies[i] > latencies[j] {
				latencies[i], latencies[j] = latencies[j], latencies[i]
			}
		}
	}
}

func percentile(p float64) float64 {
	latenciesMu.Lock()
	defer latenciesMu.Unlock()
	if len(latencies) == 0 {
		return 0
	}
	index := int(float64(len(latencies)) * p / 100)
	if index >= len(latencies) {
		index = len(latencies) - 1
	}
	return latencies[index]
}

func printMetrics(m SystemMetrics) {
	fmt.Printf("  CPU Usage:     %.2f%%\n", m.CPUPercent)
	fmt.Printf("  Memory Usage: %.2f%% (%d / %d MB)\n", m.MemoryPercent, m.MemoryUsedMB, m.MemoryTotalMB)
	fmt.Printf("  Goroutines:   %d\n", m.Goroutines)
}

func printResult(r Result) {
	fmt.Println()
	fmt.Println("========== Test Results ==========")
	fmt.Printf("  Total Sent:     %d\n", r.TotalSent)
	fmt.Printf("  Success:        %d (%.2f%%)\n", r.TotalSuccess, float64(r.TotalSuccess)/float64(r.TotalSent)*100)
	fmt.Printf("  Failed:         %d (%.2f%%)\n", r.TotalFailed, float64(r.TotalFailed)/float64(r.TotalSent)*100)
	fmt.Printf("  Duration:       %v\n", r.Duration)
	fmt.Printf("  Actual RPS:     %.2f\n", r.RequestsPerSec)
	fmt.Println()
	fmt.Printf("  Latency (ms):\n")
	fmt.Printf("    Avg:  %.2f\n", r.AvgLatencyMs)
	fmt.Printf("    Min:  %.2f\n", r.MinLatencyMs)
	fmt.Printf("    Max:  %.2f\n", r.MaxLatencyMs)
	fmt.Printf("    P95:  %.2f\n", r.P95LatencyMs)
	fmt.Printf("    P99:  %.2f\n", r.P99LatencyMs)
	fmt.Println()
	fmt.Println("========== Load Metrics ==========")
}

func saveReport(t TestRun) {
	data, err := json.MarshalIndent(t, "", "  ")
	if err != nil {
		fmt.Printf("Error saving report: %v\n", err)
		return
	}
	filename := fmt.Sprintf("stress_test_%s_%s.json", t.TestName, time.Now().Format("20060102_150405"))
	err = os.WriteFile(filename, data, 0644)
	if err != nil {
		fmt.Printf("Error writing file: %v\n", err)
	}
}
