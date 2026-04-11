package encoder

import (
	"strings"
	"unicode"
)

const (
	GSM7SingleMax = 160
	GSM7ConcatMax = 153
	UCS2SingleMax = 70
	UCS2ConcatMax = 67
)

type SmsEncoder struct{}

func NewSmsEncoder() *SmsEncoder {
	return &SmsEncoder{}
}

type EncodingResult struct {
	Encoding    string
	SmsCount    int
	Segments    []string
	IsSupported bool
}

func (e *SmsEncoder) DetectEncoding(content string) string {
	for _, r := range content {
		if r > 0xFFFE {
			return "UCS2"
		}
		if r > 0x7F {
			return "UCS2"
		}
		if !isGSM7Char(r) {
			return "UCS2"
		}
	}
	return "GSM7"
}

func (e *SmsEncoder) Encode(content string) (*EncodingResult, error) {
	encoding := e.DetectEncoding(content)

	result := &EncodingResult{
		Encoding:    encoding,
		IsSupported: true,
	}

	switch encoding {
	case "GSM7":
		result.SmsCount, result.Segments = e.splitGSM7(content)
	case "UCS2":
		result.SmsCount, result.Segments = e.splitUCS2(content)
	default:
		result.IsSupported = false
	}

	return result, nil
}

func (e *SmsEncoder) splitGSM7(content string) (int, []string) {
	if len(content) <= GSM7SingleMax {
		return 1, []string{content}
	}

	var segments []string
	remainder := content

	for len(remainder) > 0 {
		if len(remainder) <= GSM7ConcatMax {
			segments = append(segments, remainder)
			break
		}
		segments = append(segments, remainder[:GSM7ConcatMax])
		remainder = remainder[GSM7ConcatMax:]
	}

	return len(segments), segments
}

func (e *SmsEncoder) splitUCS2(content string) (int, []string) {
	runes := []rune(content)
	if len(runes) <= UCS2SingleMax {
		return 1, []string{content}
	}

	var segments []string
	for i := 0; i < len(runes); i += UCS2ConcatMax {
		end := i + UCS2ConcatMax
		if end > len(runes) {
			end = len(runes)
		}
		segments = append(segments, string(runes[i:end]))
	}

	return len(segments), segments
}

func isGSM7Char(r rune) bool {
	if r >= 0x00 && r <= 0x7F {
		if r == 0x24 {
			return true
		}
		if r >= 0x20 && r <= 0x5F {
			return true
		}
		if r >= 0x61 && r <= 0x7A {
			return true
		}
		switch r {
		case 0x0A, 0x0D, 0x40:
			return true
		}
	}
	return false
}

func (e *SmsEncoder) ValidateContent(content string) (bool, string) {
	if strings.TrimSpace(content) == "" {
		return false, "短信内容不能为空"
	}

	hasUnsupported := false
	var unsupportedChars []rune

	for _, r := range content {
		if r == 0x0D || r == 0x0A {
			continue
		}
		if r < 0x20 && r != 0x09 {
			hasUnsupported = true
			unsupportedChars = append(unsupportedChars, r)
			break
		}
		if r >= 0x80 && r <= 0x9F {
			hasUnsupported = true
			unsupportedChars = append(unsupportedChars, r)
			break
		}
		if r == 0x2028 || r == 0x2029 {
			hasUnsupported = true
			unsupportedChars = append(unsupportedChars, r)
			break
		}
	}

	if hasUnsupported {
		return false, "短信内容包含不支持的字符"
	}

	return true, ""
}

func (e *SmsEncoder) CountSmsParts(content string) int {
	result, _ := e.Encode(content)
	return result.SmsCount
}

func isEmoji(r rune) bool {
	if r >= 0x1F300 && r <= 0x1F9FF {
		return true
	}
	if r >= 0x2600 && r <= 0x26FF {
		return true
	}
	if r >= 0x2700 && r <= 0x27BF {
		return true
	}
	if r >= 0xFE00 && r <= 0xFE0F {
		return true
	}
	return false
}

func HasEmoji(content string) bool {
	for _, r := range content {
		if unicode.Is(unicode.Other, r) && isEmoji(r) {
			return true
		}
		if isEmoji(r) {
			return true
		}
	}
	return false
}

func RemoveEmoji(content string) string {
	var result strings.Builder
	for _, r := range content {
		if !isEmoji(r) {
			result.WriteRune(r)
		}
	}
	return result.String()
}
