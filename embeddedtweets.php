<?php
/*
 * Embedded Tweets
 * Author: Cameron de Witte
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('parse_message', 'embeddedtweets_parse_message');

function embeddedtweets_info() {
	return array(
	'name'			=> 'Embedded Tweets',
	'description'	=> 'A plugin that provides a simple MyCode for displaying Tweets inline',
	'website'		=> 'https://github.com/Cameron-D/MyBB-Embedded-Tweets',
	'author'		=> 'Cameron:D',
	'authorsite'	=> 'http://cazzaserver.com/',
	'version'		=> '0.1',
	'compatibility'	=> '16*,17*',
	'guid'			=> 'ef2f8f54c68952db7f6cf48c77e716c5'
	);
}

function embeddedtweets_parse_message($message) {
	$twitterregex = "~\[twitter\](https?:\/\/twitter\.com/[A-Za-z0-9_]+/status[es]*/)?(\d+)/?\[\/twitter\]~s";
	preg_match_all($twitterregex, $message, $matches, PREG_SET_ORDER);
	
	if(!$matches)
		return $message;
		
	foreach($matches as &$match) {
		$tweetjson = embeddedtweets_fetch_tweet_json($match[2]);
		if($tweetjson && !isset($tweetjson->errors)) {
			$message = str_replace($match[0], $tweetjson->html, $message);
		} else {
			if (empty($match[1])) {
				$message = str_replace($match[0], "Error fetching information for tweet", $message);
			} else {
				$message = str_replace($match[0], "Error fetching information for tweet. <a href=\"{$match[1]}{$match[2]}\">Link to tweet</a>", $message);
			}
		}
	}
	return $message;
}

function embeddedtweets_fetch_tweet_json($tweetid) {
	global $cache;
	$tweetdata = $cache->read("tweet_{$tweetid}");
	if($tweetdata === false) {
		$url = "https://api.twitter.com/1/statuses/oembed.json?id={$tweetid}";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "MyBB/EmbeddedTweet-Plugin");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$http_result = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if($http_code != 200) {
			$tweetdata = false;
		} else {
			$tweetdata = json_decode($http_result);
			$cache->update("tweet_{$tweetid}", $tweetdata);
   		}
	}
	return $tweetdata;
}
?>