package model

type CountryInfo struct {
	Code string `json:"code"`
	Name string `json:"name"`
}

var CountryList = []CountryInfo{
	{"CN", "中国"},
	{"HK", "中国香港特别行政区"},
	{"MO", "中国澳门特别行政区"},
	{"TW", "台湾"},
	{"JP", "日本"},
	{"KR", "韩国"},
	{"IN", "印度"},
	{"SG", "新加坡"},
	{"MY", "马来西亚"},
	{"TH", "泰国"},
	{"VN", "越南"},
	{"PH", "菲律宾"},
	{"ID", "印度尼西亚"},
	{"AU", "澳大利亚"},
	{"NZ", "新西兰"},
	{"US", "美国"},
	{"CA", "加拿大"},
	{"MX", "墨西哥"},
	{"BR", "巴西"},
	{"AR", "阿根廷"},
	{"GB", "英国"},
	{"DE", "德国"},
	{"FR", "法国"},
	{"IT", "意大利"},
	{"ES", "西班牙"},
	{"NL", "荷兰"},
	{"BE", "比利时"},
	{"CH", "瑞士"},
	{"AT", "奥地利"},
	{"PT", "葡萄牙"},
	{"RU", "俄罗斯"},
	{"AE", "阿拉伯联合酋长国"},
	{"SA", "沙特阿拉伯"},
	{"ZA", "南非"},
	{"EG", "埃及"},
	{"NG", "尼日利亚"},
}

func GetCountryInfoByCode(code string) *CountryInfo {
	for i := range CountryList {
		if CountryList[i].Code == code {
			return &CountryList[i]
		}
	}
	return nil
}
