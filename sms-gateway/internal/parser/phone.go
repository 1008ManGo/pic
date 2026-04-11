package parser

import (
	"errors"
	"regexp"
	"strings"
)

var (
	ErrEmptyPhone    = errors.New("phone number is empty")
	ErrInvalidFormat = errors.New("invalid phone format")
	ErrInvalidPrefix = errors.New("invalid country prefix")
	ErrInvalidLength = errors.New("invalid phone length")
)

type PhoneParser struct {
	validPrefixes map[string]bool
}

func NewPhoneParser() *PhoneParser {
	return &PhoneParser{
		validPrefixes: map[string]bool{
			"+1": true, "+7": true, "+20": true, "+27": true, "+30": true,
			"+31": true, "+32": true, "+33": true, "+34": true, "+36": true,
			"+39": true, "+40": true, "+41": true, "+43": true, "+44": true,
			"+45": true, "+46": true, "+47": true, "+48": true, "+49": true,
			"+51": true, "+52": true, "+53": true, "+54": true, "+55": true,
			"+56": true, "+57": true, "+58": true, "+60": true, "+61": true,
			"+62": true, "+63": true, "+64": true, "+65": true, "+66": true,
			"+81": true, "+82": true, "+84": true, "+86": true, "+90": true,
			"+91": true, "+92": true, "+93": true, "+94": true, "+95": true,
			"+98": true, "+212": true, "+213": true, "+216": true, "+218": true,
			"+220": true, "+221": true, "+222": true, "+223": true, "+224": true,
			"+225": true, "+226": true, "+227": true, "+228": true, "+229": true,
			"+230": true, "+231": true, "+232": true, "+233": true, "+234": true,
			"+235": true, "+236": true, "+237": true, "+238": true, "+239": true,
			"+240": true, "+241": true, "+242": true, "+243": true, "+244": true,
			"+245": true, "+246": true, "+247": true, "+248": true, "+249": true,
			"+250": true, "+251": true, "+252": true, "+253": true, "+254": true,
			"+255": true, "+256": true, "+257": true, "+258": true, "+260": true,
			"+261": true, "+262": true, "+263": true, "+264": true, "+265": true,
			"+266": true, "+267": true, "+268": true, "+269": true, "+290": true,
			"+291": true, "+297": true, "+298": true, "+299": true, "+350": true,
			"+351": true, "+352": true, "+353": true, "+354": true, "+355": true,
			"+356": true, "+357": true, "+358": true, "+359": true, "+370": true,
			"+371": true, "+372": true, "+373": true, "+374": true, "+375": true,
			"+376": true, "+377": true, "+378": true, "+380": true, "+381": true,
			"+382": true, "+383": true, "+385": true, "+386": true, "+387": true,
			"+389": true, "+420": true, "+421": true, "+423": true, "+501": true,
			"+502": true, "+503": true, "+504": true, "+505": true, "+506": true,
			"+507": true, "+508": true, "+509": true, "+590": true, "+591": true,
			"+592": true, "+593": true, "+595": true, "+597": true, "+598": true,
			"+599": true, "+670": true, "+672": true, "+673": true, "+674": true,
			"+675": true, "+676": true, "+677": true, "+678": true, "+679": true,
			"+680": true, "+681": true, "+682": true, "+683": true, "+685": true,
			"+687": true, "+688": true, "+689": true, "+850": true, "+852": true,
			"+853": true, "+855": true, "+856": true, "+880": true, "+886": true,
			"+960": true, "+961": true, "+962": true, "+963": true, "+964": true,
			"+965": true, "+966": true, "+967": true, "+968": true, "+970": true,
			"+971": true, "+972": true, "+973": true, "+974": true, "+975": true,
			"+976": true, "+977": true, "+992": true, "+993": true, "+994": true,
			"+995": true, "+996": true, "+998": true,
		},
	}
}

func (p *PhoneParser) Normalize(phone string, countryCode string) (string, error) {
	phone = strings.TrimSpace(phone)
	if phone == "" {
		return "", ErrEmptyPhone
	}

	phone = strings.ReplaceAll(phone, " ", "")
	phone = strings.ReplaceAll(phone, "-", "")
	phone = strings.ReplaceAll(phone, "(", "")
	phone = strings.ReplaceAll(phone, ")", "")

	if strings.HasPrefix(phone, "00") {
		phone = "+" + phone[2:]
	}

	if !strings.HasPrefix(phone, "+") && !strings.HasPrefix(phone, "86") && !strings.HasPrefix(phone, "1") {
		phone = "+" + phone
	}

	if strings.HasPrefix(phone, "+86") && len(phone) == 12 {
		phone = "+" + phone[1:]
	}

	if err := p.Validate(phone); err != nil {
		return "", err
	}

	return phone, nil
}

func (p *PhoneParser) Validate(phone string) error {
	if !strings.HasPrefix(phone, "+") {
		return ErrInvalidFormat
	}

	prefix := p.getCountryPrefix(phone)
	if prefix == "" {
		return ErrInvalidPrefix
	}

	if len(phone) < 8 || len(phone) > 20 {
		return ErrInvalidLength
	}

	matched, _ := regexp.MatchString(`^\+\d{6,15}$`, phone)
	if !matched {
		return ErrInvalidFormat
	}

	return nil
}

func (p *PhoneParser) getCountryPrefix(phone string) string {
	if len(phone) < 2 {
		return ""
	}

	for i := len(phone); i >= 2; i-- {
		prefix := phone[:i]
		if _, ok := p.validPrefixes[prefix]; ok {
			return prefix
		}
	}
	return ""
}

func ExtractCountryCode(phone string) string {
	if !strings.HasPrefix(phone, "+") {
		return ""
	}

	phone = phone[1:]
	switch {
	case strings.HasPrefix(phone, "86"):
		return "CN"
	case strings.HasPrefix(phone, "1"):
		return "US"
	case strings.HasPrefix(phone, "44"):
		return "GB"
	case strings.HasPrefix(phone, "81"):
		return "JP"
	case strings.HasPrefix(phone, "82"):
		return "KR"
	case strings.HasPrefix(phone, "91"):
		return "IN"
	default:
		return ""
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
