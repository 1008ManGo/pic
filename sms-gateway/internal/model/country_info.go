package model

type CountryInfo struct {
	Code   string `json:"code"`
	Name   string `json:"name"`
	Prefix string `json:"prefix"`
}

var CountryList = []CountryInfo{
	{"CN", "中国", "+86"},
	{"HK", "香港", "+852"},
	{"MO", "澳门", "+853"},
	{"TW", "台湾", "+886"},
	{"US", "美国", "+1"},
	{"GB", "英国", "+44"},
	{"JP", "日本", "+81"},
	{"KR", "韩国", "+82"},
	{"IN", "印度", "+91"},
	{"SG", "新加坡", "+65"},
	{"MY", "马来西亚", "+60"},
	{"TH", "泰国", "+66"},
	{"VN", "越南", "+84"},
	{"PH", "菲律宾", "+63"},
	{"ID", "印度尼西亚", "+62"},
	{"AU", "澳大利亚", "+61"},
	{"NZ", "新西兰", "+64"},
	{"CA", "加拿大", "+1"},
	{"DE", "德国", "+49"},
	{"FR", "法国", "+33"},
	{"IT", "意大利", "+39"},
	{"ES", "西班牙", "+34"},
	{"NL", "荷兰", "+31"},
	{"BE", "比利时", "+32"},
	{"CH", "瑞士", "+41"},
	{"AT", "奥地利", "+43"},
	{"PT", "葡萄牙", "+351"},
	{"BR", "巴西", "+55"},
	{"MX", "墨西哥", "+52"},
	{"AR", "阿根廷", "+54"},
	{"RU", "俄罗斯", "+7"},
	{"AE", "阿联酋", "+971"},
	{"SA", "沙特阿拉伯", "+966"},
	{"EG", "埃及", "+20"},
	{"NG", "尼日利亚", "+234"},
	{"ZA", "南非", "+27"},
}

func GetCountryInfoByCode(code string) *CountryInfo {
	for i := range CountryList {
		if CountryList[i].Code == code {
			return &CountryList[i]
		}
	}
	return nil
}
