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
	phone = strings.TrimSpace(phone)
	if phone == "" {
		return "", ErrEmptyPhone
	}

	phone = strings.ReplaceAll(phone, " ", "")
	phone = strings.ReplaceAll(phone, "-", "")
	phone = strings.ReplaceAll(phone, "(", "")
	phone = strings.ReplaceAll(phone, ")", "")

	region := defaultCountry
	if region == "" {
		region = "US"
	}

	parsed, err := phonenumbers.Parse(phone, region)
	if err != nil {
		return "", ErrInvalidFormat
	}

	if !phonenumbers.IsValidNumber(parsed) {
		return "", ErrInvalidFormat
	}

	return phonenumbers.Format(parsed, phonenumbers.E164), nil
}

func (p *PhoneParser) Validate(phone string) error {
	phone = strings.TrimSpace(phone)
	if phone == "" {
		return ErrEmptyPhone
	}

	parsed, err := phonenumbers.Parse(phone, "US")
	if err != nil {
		return ErrInvalidFormat
	}

	if !phonenumbers.IsValidNumber(parsed) {
		return ErrInvalidFormat
	}

	return nil
}

func ExtractCountryCode(phone string) string {
	phone = strings.TrimSpace(phone)
	if phone == "" {
		return ""
	}

	phone = strings.ReplaceAll(phone, " ", "")
	phone = strings.ReplaceAll(phone, "-", "")
	phone = strings.ReplaceAll(phone, "(", "")
	phone = strings.ReplaceAll(phone, ")", "")

	parsed, err := phonenumbers.Parse(phone, "US")
	if err != nil {
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
