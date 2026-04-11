package parser

import (
	"errors"
	"strings"

	"github.com/nyaruka/phonenumbers"
)

var (
	ErrEmptyPhone    = errors.New("phone number is empty")
	ErrInvalidFormat = errors.New("invalid phone format")
	ErrInvalidPrefix = errors.New("invalid country prefix")
	ErrInvalidLength = errors.New("invalid phone length")
)

type PhoneParser struct{}

func NewPhoneParser() *PhoneParser {
	return &PhoneParser{}
}

func (p *PhoneParser) Normalize(phone string, defaultCountry string) (string, error) {
	input := strings.TrimSpace(phone)
	if input == "" {
		return "", ErrEmptyPhone
	}

	input = strings.ReplaceAll(input, " ", "")
	input = strings.ReplaceAll(input, "-", "")
	input = strings.ReplaceAll(input, "(", "")
	input = strings.ReplaceAll(input, ")", "")

	if strings.HasPrefix(input, "00") {
		input = "+" + input[2:]
	}

	if !strings.HasPrefix(input, "+") {
		input = "+" + input
	}

	region := defaultCountry
	if region == "" {
		region = "US"
	}

	parsed, err := phonenumbers.Parse(input, region)
	if err != nil {
		return "", ErrInvalidFormat
	}

	if !phonenumbers.IsPossibleNumber(parsed) {
		return "", ErrInvalidFormat
	}

	if !phonenumbers.IsValidNumber(parsed) {
		return "", ErrInvalidFormat
	}

	return phonenumbers.Format(parsed, phonenumbers.E164), nil
}

func (p *PhoneParser) Validate(phone string) error {
	input := strings.TrimSpace(phone)
	if input == "" {
		return ErrEmptyPhone
	}

	input = strings.ReplaceAll(input, " ", "")
	input = strings.ReplaceAll(input, "-", "")
	input = strings.ReplaceAll(input, "(", "")
	input = strings.ReplaceAll(input, ")", "")

	if strings.HasPrefix(input, "00") {
		input = "+" + input[2:]
	}

	if !strings.HasPrefix(input, "+") {
		input = "+" + input
	}

	parsed, err := phonenumbers.Parse(input, "")
	if err != nil {
		return ErrInvalidFormat
	}

	if !phonenumbers.IsPossibleNumber(parsed) {
		return ErrInvalidFormat
	}

	if !phonenumbers.IsValidNumber(parsed) {
		return ErrInvalidFormat
	}

	return nil
}

func ExtractCountryCode(phone string) string {
	input := strings.TrimSpace(phone)
	if input == "" {
		return ""
	}

	input = strings.ReplaceAll(input, " ", "")
	input = strings.ReplaceAll(input, "-", "")
	input = strings.ReplaceAll(input, "(", "")
	input = strings.ReplaceAll(input, ")", "")

	if strings.HasPrefix(input, "00") {
		input = "+" + input[2:]
	}

	if !strings.HasPrefix(input, "+") {
		return ""
	}

	parsed, err := phonenumbers.Parse(input, "")
	if err != nil {
		return ""
	}

	if !phonenumbers.IsValidNumber(parsed) {
		return ""
	}

	region := phonenumbers.GetRegionCodeForNumber(parsed)
	if region == "" {
		return ""
	}

	switch region {
	case "HK":
		return "HK"
	case "MO":
		return "MO"
	case "TW":
		return "TW"
	case "CN":
		return "CN"
	default:
		return region
	}
}

type ParseError struct {
	Phone string
	Err   error
}

func (e *ParseError) Error() string {
	return e.Phone + ": " + e.Err.Error()
}

func NormalizePhones(phones []string, defaultCountry string) ([]string, []ParseError) {
	parser := NewPhoneParser()
	var valid []string
	var errors []ParseError

	for _, phone := range phones {
		normalized, err := parser.Normalize(phone, defaultCountry)
		if err != nil {
			errors = append(errors, ParseError{Phone: phone, Err: err})
			continue
		}
		valid = append(valid, normalized)
	}

	return valid, errors
}
