<?php

declare(strict_types=1);

/*
 *  __  __       _       _  ____                 _              __   _  _
 * |  \/  |_   _| | __ _(_)/ ___| __ _ _ __ ___ (_)_ __   __ _ / /_ | || |
 * | |\/| | | | | |/ _` | | |  _ / _` | '_ ` _ \| | '_ \ / _` | '_ \| || |_
 * | |  | | |_| | | (_| | | |_| | (_| | | | | | | | | | | (_| | (_) |__   _|
 * |_|  |_|\__,_|_|\__, |_|\____|\__,_|_| |_| |_|_|_| |_|\__, |\___/   |_|
 *                    |_|                                |___/
 *
 * Copyright (c) 2022 MulqiGaming64
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace MulqiGaming64\LibYoutubeDetail\Task;

use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetail;
use MulqiGaming64\LibYoutubeDetail\LibYoutubeDetailException;
use pocketmine\scheduler\AsyncTask;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function date;
use function explode;
use function filter_var;
use function json_decode;
use function json_encode;
use function simplexml_load_string;
use function str_replace;
use function strtotime;

class LibYoutubeTask extends AsyncTask{

	/** @param LibYoutubeDetail $yt */
	private static $yt;

	/** @param string $url */
	private $url;

	public function __construct(LibYoutubeDetail $yt, string $url){
		$this->yt = $yt;
		$this->url = $url;
	}

	public static function getCurl(string $url) : array{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		$result = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ["result" => $result, "http_code" => $code];
	}

	/*
	* @param string $name
	*/
	public function getDetailFromChannel(string $name = "MulqiGaming64") : array{
		$curl = self::getCurl("https://www.youtube.com/c/" . $name);
		if($curl["http_code"] == 404){
			// Trying with youtube url id
			$curl = self::getCurl("https://www.youtube.com/channel/" . $name);
			if($curl["http_code"] == 404){
				throw new LibYoutubeDetailException("Channel not found");
			}
		}

		$result = $curl["result"];
		$ex = explode("\n", $result);
		$ex = explode("var ytInitialData = ", $ex[25]);
		$ex = explode(";</script><script nonce=", $ex[1]);

		$js = json_decode($ex[0], true);
		$metadata = $js["metadata"]["channelMetadataRenderer"];
		$header = $js["header"]["c4TabbedHeaderRenderer"];

		$return = [];

		$return["channel"] = [];
		$return["channel"]["name"] = $metadata["title"];
		$return["channel"]["description"] = $metadata["description"];
		$return["channel"]["id"] = $metadata["externalId"];
		$return["channel"]["url"] = $metadata["ownerUrls"][0];
		$return["channel"]["subscriber"] = (int) str_replace(" subscriber", "", $header["subscriberCountText"]["simpleText"]) + 0;
		$return["channel"]["familySafe"] = (bool) $metadata["isFamilySafe"];
		$return["channel"]["availableCountryCodes"] = (array) $metadata["availableCountryCodes"];
		$return["channel"]["banner"] = [];
		$return["channel"]["banner"]["url"] = $header["banner"]["thumbnails"][0]["url"];
		$return["channel"]["banner"]["width"] = $header["banner"]["thumbnails"][0]["width"];
		$return["channel"]["banner"]["height"] = $header["banner"]["thumbnails"][0]["height"];
		$return["channel"]["avatar"] = [];
		$return["channel"]["avatar"]["url"] = $metadata["avatar"]["thumbnails"][0]["url"];
		$return["channel"]["avatar"]["width"] = $metadata["avatar"]["thumbnails"][0]["width"];
		$return["channel"]["avatar"]["height"] = $metadata["avatar"]["thumbnails"][0]["height"];

		$return["videos"] = [];

		$videos = self::getCurl($metadata["rssUrl"])["result"];
		$videos = json_decode(json_encode(simplexml_load_string($videos, "SimpleXMLElement", LIBXML_NOCDATA)), true);
		foreach($videos["entry"] as $index => $value){
			$return["videos"][$index] = [];
			$return["videos"][$index]["title"] = $value["title"];
			$return["videos"][$index]["id"] = str_replace("yt:video:", "", $value["id"]);
			$return["videos"][$index]["url"] = $value["link"]["@attributes"]["href"];
			$return["videos"][$index]["author"] = $value["author"]["name"];
			$return["videos"][$index]["published"] = date("d M Y", strtotime($value["published"]));
			$return["videos"][$index]["updated"] = date("d M Y", strtotime($value["updated"]));
		}

		return $return;
	}

	/*
	* @param string $url
	*/
	public function getDetailFromUrl(string $url = "https://www.youtube.com/c/MulqiGaming64") : array{
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			throw new LibYoutubeDetailException("Please input Url");
		}

		$curl = self::getCurl($url);
		if($curl["http_code"] == 404){
			throw new LibYoutubeDetailException("Channel not found");
		}

		$result = $curl["result"];
		$ex = explode("\n", $result);
		$ex = explode("var ytInitialData = ", $ex[25]);
		$ex = explode(";</script><script nonce=", $ex[1]);

		$js = json_decode($ex[0], true);
		$metadata = $js["metadata"]["channelMetadataRenderer"];
		$header = $js["header"]["c4TabbedHeaderRenderer"];

		$return = [];

		$return["channel"] = [];
		$return["channel"]["name"] = $metadata["title"];
		$return["channel"]["description"] = $metadata["description"];
		$return["channel"]["id"] = $metadata["externalId"];
		$return["channel"]["url"] = $metadata["ownerUrls"][0];
		$return["channel"]["subscriber"] = (int) str_replace(" subscriber", "", $header["subscriberCountText"]["simpleText"]) + 0;
		$return["channel"]["familySafe"] = (bool) $metadata["isFamilySafe"];
		$return["channel"]["availableCountryCodes"] = (array) $metadata["availableCountryCodes"];
		$return["channel"]["banner"] = [];
		$return["channel"]["banner"]["url"] = $header["banner"]["thumbnails"][0]["url"];
		$return["channel"]["banner"]["width"] = $header["banner"]["thumbnails"][0]["width"];
		$return["channel"]["banner"]["height"] = $header["banner"]["thumbnails"][0]["height"];
		$return["channel"]["avatar"] = [];
		$return["channel"]["avatar"]["url"] = $metadata["avatar"]["thumbnails"][0]["url"];
		$return["channel"]["avatar"]["width"] = $metadata["avatar"]["thumbnails"][0]["width"];
		$return["channel"]["avatar"]["height"] = $metadata["avatar"]["thumbnails"][0]["height"];

		$return["videos"] = [];

		$videos = self::getCurl($metadata["rssUrl"])["result"];
		$videos = json_decode(json_encode(simplexml_load_string($videos, "SimpleXMLElement", LIBXML_NOCDATA)), true);
		foreach($videos["entry"] as $index => $value){
			$return["videos"][$index] = [];
			$return["videos"][$index]["title"] = $value["title"];
			$return["videos"][$index]["id"] = str_replace("yt:video:", "", $value["id"]);
			$return["videos"][$index]["url"] = $value["link"]["@attributes"]["href"];
			$return["videos"][$index]["author"] = $value["author"]["name"];
			$return["videos"][$index]["published"] = date("d M Y", strtotime($value["published"]));
			$return["videos"][$index]["updated"] = date("d M Y", strtotime($value["updated"]));
		}

		return $return;
	}

	public function onRun() : void{
		$result = null;

		if(!filter_var($this->url, FILTER_VALIDATE_URL)){
			try {
				$result = $this->getDetailFromChannel($this->url);
			} catch(LibYoutubeDetailException $error){
				// No Action
			}
		} else {
			try {
				$result = $this->getDetailFromUrl($this->url);
			} catch(LibYoutubeDetailException $error){
				// No Action
			}
		}

		$this->setResult($result);
	}

	public function onCompletion() : void{
		$this->yt->callback($this->getResult());
	}
}
