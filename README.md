# LibYoutubeDetail
A PocketMine Virion which allows plugins to get detail information from Youtube Channel

# Need Extension
In pocketmine by default is On
- Json
- Curl
- SimpleXML

# Default API
```php
use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetail;
use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetailException;

// Begin Start Api
$youtube = new LibYoutubeDetail();

// Getting channel detail
// From Name or Id
$detail = $youtube->getDetailFromChannel("MulqiGaming64");
// From Url
$detail = $youtube->getDetailFromUrl("https://www.youtube.com/c/MulqiGaming64");

// You can also use try catch for getting error like channel not found
try {
	$detail = $youtube->getDetailFromChannel("MulqiGaming64");
} catch(LibYoutubeDetailException $error){
	var_dump($error->getMessage());
}

// Getting information
var_dump($detail);
// All data from information there are the number of subscribers, videos, avatar urls, banners and others.
```

# Async API
```php
use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetail;
use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetailException;

// Begin Start Api
$youtube = new LibYoutubeDetail();

// you can also retrieve data with Callable, enable async so it doesn't lag
// Set The async
$youtube->setAsync();
// Set The callable returned value is array or null ( If the channel doesn't exist )
$youtube->setCallable(function (?array $detail): void{
	var_dump($detail);
});

// in this section there is no need try catch because if error will return null
// Set the url or channel name
$youtube->getDetailFromChannel("MulqiGaming64"); // If async on returned value is null
// From Url
$youtube->getDetailFromUrl("https://www.youtube.com/c/MulqiGaming64"); // If async on returned value is null

// All data from information there are the number of subscribers, videos, avatar urls, banners and others.
```