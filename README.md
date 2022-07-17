# LibYoutubeDetail
A PocketMine Virion which allows plugins to get detail information from Youtube Channel

# Need Extension
- Json
- Curl
- SimpleXML

# API
```php
use use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetail;
use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetailException;

// Begin Start Api
$youtube = new LibYoutubeDetail();

// Getting channel detail
// From Name or Id
$detail = $youtube->getDetailFromChannel("MulqiGaming64");
// From Url
$detail = $youtube->getDetailFromUrl("https://www.youtube.com/c/MulqiGaming64");

// Getting information
var_dump($detail);
// All data from information there are the number of subscribers, videos, avatar urls, banners and others.
```