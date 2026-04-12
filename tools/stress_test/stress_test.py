#!/usr/bin/env python3
"""
SMS Gateway Stress Test Tool
Tests SMS sending at configurable TPS and message volumes
"""

import asyncio
import aiohttp
import argparse
import json
import time
import statistics
import os
import sys
from datetime import datetime
from collections import defaultdict
from typing import List, Dict

class StressTest:
    def __init__(self, api_url: str, token: str, total_sms: int, tps: int, 
                 phone_prefix: str, content: str, sender_id: str):
        self.api_url = api_url
        self.token = token
        self.total_sms = total_sms
        self.target_tps = tps
        self.phone_prefix = phone_prefix
        self.content = content
        self.sender_id = sender_id
        
        self.sent = 0
        self.success = 0
        self.failed = 0
        self.latencies: List[float] = []
        self.errors: Dict[str, int] = defaultdict(int)
        self.start_time = None
        self.end_time = None
        
    async def send_sms(self, session: aiohttp.ClientSession, phone: str):
        """Send a single SMS and record latency"""
        start = time.time()
        try:
            payload = {
                "phones": [phone],
                "content": self.content
            }
            if self.sender_id:
                payload["sender_id"] = self.sender_id
            
            headers = {
                "Authorization": f"Bearer {self.token}",
                "Content-Type": "application/json"
            }
            
            async with session.post(self.api_url, json=payload, headers=headers) as resp:
                await resp.json()
                latency = (time.time() - start) * 1000
                
                self.latencies.append(latency)
                if resp.status == 200:
                    self.success += 1
                else:
                    self.failed += 1
                    
        except Exception as e:
            self.failed += 1
            self.errors[str(type(e).__name__)] += 1
            
        self.sent += 1
        
    async def run(self):
        """Run the stress test"""
        self.start_time = datetime.now()
        
        connector = aiohttp.TCPConnector(limit=self.target_tps * 2)
        timeout = aiohttp.ClientTimeout(total=30)
        
        async with aiohttp.ClientSession(connector=connector, timeout=timeout) as session:
            interval = 1.0 / self.target_tps if self.target_tps > 0 else 0
            
            tasks = []
            for i in range(self.total_sms):
                phone = f"{self.phone_prefix}{447000000 + i % 10000000}"
                tasks.append(self.send_sms(session, phone))
                
                if interval > 0 and (i + 1) % self.target_tps == 0:
                    await asyncio.sleep(interval)
                    
                if (i + 1) % 1000 == 0:
                    print(f"\r  Progress: {i + 1} / {self.total_sms} ({(i+1)*100/self.total_sms:.1f}%)", end="", flush=True)
            
            await asyncio.gather(*tasks)
            
        self.end_time = datetime.now()
        
    def get_results(self) -> dict:
        """Calculate and return test results"""
        duration = (self.end_time - self.start_time).total_seconds()
        
        sorted_latencies = sorted(self.latencies) if self.latencies else [0]
        p95_idx = int(len(sorted_latencies) * 0.95)
        p99_idx = int(len(sorted_latencies) * 0.99)
        
        return {
            "test_name": f"{self.total_sms}sms_{self.target_tps}tps",
            "config": {
                "api_url": self.api_url,
                "total_sms": self.total_sms,
                "target_tps": self.target_tps,
                "phone_prefix": self.phone_prefix,
                "content": self.content,
                "sender_id": self.sender_id
            },
            "results": {
                "total_sent": self.sent,
                "total_success": self.success,
                "total_failed": self.failed,
                "success_rate": (self.success / self.sent * 100) if self.sent > 0 else 0,
                "duration_seconds": duration,
                "actual_rps": self.sent / duration if duration > 0 else 0,
                "latency_ms": {
                    "avg": statistics.mean(self.latencies) if self.latencies else 0,
                    "min": min(sorted_latencies) if sorted_latencies else 0,
                    "max": max(sorted_latencies) if sorted_latencies else 0,
                    "p50": sorted_latencies[p50_idx := len(sorted_latencies)//2] if sorted_latencies else 0,
                    "p95": sorted_latencies[p95_idx] if sorted_latencies else 0,
                    "p99": sorted_latencies[p99_idx] if sorted_latencies else 0,
                    "stddev": statistics.stdev(self.latencies) if len(self.latencies) > 1 else 0
                }
            },
            "errors": dict(self.errors),
            "start_time": self.start_time.isoformat(),
            "end_time": self.end_time.isoformat()
        }

async def get_system_metrics():
    """Get current system metrics"""
    try:
        import psutil
        return {
            "cpu_percent": psutil.cpu_percent(interval=0.1),
            "memory_percent": psutil.virtual_memory().percent,
            "memory_used_mb": psutil.virtual_memory().used / 1024 / 1024,
            "goroutines": 1  # Not available in Python
        }
    except ImportError:
        return {
            "cpu_percent": 0,
            "memory_percent": 0,
            "memory_used_mb": 0
        }

async def run_test_suite():
    parser = argparse.ArgumentParser(description="SMS Gateway Stress Test")
    parser.add_argument("--api", default="http://localhost:8080/api/sms/send", help="SMS API URL")
    parser.add_argument("--token", required=True, help="Auth token")
    parser.add_argument("--count", type=int, default=1000, help="Total SMS to send")
    parser.add_argument("--tps", type=int, default=50, help="Target TPS")
    parser.add_argument("--prefix", default="+447", help="Phone number prefix")
    parser.add_argument("--content", default="压力测试消息", help="SMS content")
    parser.add_argument("--sender", default="SYSTEST", help="Sender ID")
    parser.add_argument("--output", default="stress_test_results", help="Output directory")
    args = parser.parse_args()
    
    os.makedirs(args.output, exist_ok=True)
    
    print("=" * 50)
    print("   SMS Gateway Stress Test")
    print("=" * 50)
    print()
    print(f"Configuration:")
    print(f"  API URL:     {args.api}")
    print(f"  Total SMS:   {args.count}")
    print(f"  Target TPS:  {args.tps}")
    print(f"  Phone Prefix: {args.prefix}")
    print()
    
    # Idle metrics
    print("[1/3] Collecting idle metrics...")
    idle_metrics = await get_system_metrics()
    print(f"  CPU: {idle_metrics['cpu_percent']:.1f}%")
    print(f"  Memory: {idle_metrics['memory_percent']:.1f}%")
    print()
    
    # Run test
    print("[2/3] Running stress test...")
    test = StressTest(
        api_url=args.api,
        token=args.token,
        total_sms=args.count,
        tps=args.tps,
        phone_prefix=args.prefix,
        content=args.content,
        sender_id=args.sender
    )
    await test.run()
    results = test.get_results()
    
    # Load metrics
    print()
    print("[3/3] Collecting load metrics...")
    load_metrics = await get_system_metrics()
    
    # Save results
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"{args.output}/result_{args.count}sms_{args.tps}tps_{timestamp}.json"
    
    full_results = {
        "test_name": results["test_name"],
        "idle_metrics": idle_metrics,
        "load_metrics": load_metrics,
        **results
    }
    
    with open(filename, "w", encoding="utf-8") as f:
        json.dump(full_results, f, indent=2, ensure_ascii=False)
    
    # Print summary
    print()
    print("=" * 50)
    print("Results")
    print("=" * 50)
    r = results["results"]
    print(f"  Total Sent:    {r['total_sent']}")
    print(f"  Success:       {r['total_success']} ({r['success_rate']:.2f}%)")
    print(f"  Failed:        {r['total_failed']}")
    print(f"  Duration:      {r['duration_seconds']:.2f}s")
    print(f"  Actual RPS:   {r['actual_rps']:.2f}")
    print()
    print(f"  Latency (ms):")
    lat = r["latency_ms"]
    print(f"    Avg:  {lat['avg']:.2f}")
    print(f"    Min:  {lat['min']:.2f}")
    print(f"    Max:  {lat['max']:.2f}")
    print(f"    P95:  {lat['p95']:.2f}")
    print(f"    P99:  {lat['p99']:.2f}")
    print()
    print(f"Results saved to: {filename}")
    
    return full_results

if __name__ == "__main__":
    asyncio.run(run_test_suite())
