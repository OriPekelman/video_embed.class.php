# VideoEmbed class  can be used to safely and cleanly embed videos from different sources

I looked for a nice clean class implementation in PHP for embedding videos from youtube and such and could not find anything that was nice enough. So here is my take on embedding videos in php.
As the embed code is reconstructed it should be safe enough put probably some more checks need to be done after extracting the id to see there is nothing hostile there.

##Licence
BSD

##Careful!
This is very old code. I am more or less releasing this as githubware (new name for abandonware). 

##Configuration:                                                                                                                             

This class requires the SpyC library to read the cobnfiguration file. The library is assumed to be in the SITEBASE/include/yaml/ directory.

`define('SITEBASE', '/var/www/mysite'); // path to the root of the site (not forcefully public)`
`define('VIDEO_EMBED_CONFIG_FILE', SITEBASE.'/config/video_embed.yaml'); //path of video embed config file`
`define('DEBUG', true); //to activate debug mode and false for production usage. it will write to a log file when something goes wrong but should not produce exceptions in production enviroment`

##USAGE
note: The embed code may either be embed or url
`
    $embed='http://www.youtube.com/watch?v=h2EUW_rgDVo';
    $videoEmbed = new VideoEmbed($embed); //optional width and height may be passed to the constructor
    print($videoEmbed->embed);
    $videoEmbed->width = 240; // resize
    $videoEmbed->height = 120;
    print($videoEmbed->embed); // resized video
    print($videoEmbed->thumb); // get thumb url
`
the other public properties are: ->id, ->type, ->url, ->width and ->height
note that magic getters and setters are used to make ->id, ->type, ->url read only

TODO: thumbnails should be cached locally
TODO: Create unit tests (for the moment test_VideoEmbed(); does some testing)


The video services are configured in the configuration file (video_embed.yaml), form:

`
---
embedTemplate: default embed template 
defaultWidth: default width
defaultHeight: default height
services:
    servicename:
        urlPattern: pattern to distinguis between services
        embedUrlTemplate: template (used with sprintf) for construvting the player url
        thumbnailUrlTemplate: template to find thumnbail by video ID (used with sprintf)
        thumbnailUrlExtractPattern: if present the thumbnailUrlTemplate is assumed to be a text resource, and this is a regexp to extract the thumnbail from it
        extractPattern: regexp pattern to extract
        apiUrl: api url (not used for the currently supported services)
        defaultWidth: service default width
        defaultHeight: service default height
		embedTemplate: specific embed template (not used for the current supported services)
`

Example configuration file for google video youtube and dailymotion (if you configure it for other services please post the config)... :)  


`
---
embedTemplate: <object width="%2$s" height="%3$s" ><param name="movie" value="%1$s"></param><param name="wmode" value="transparent"></param><embed src="%1$s" type="application/x-shockwave-flash" wmode="transparent" width="%2$s" height="%3$s"></embed></object>
defaultWidth: 425
defaultHeight: 350
services:
    youtube:
        urlPattern: youtube.com
        embedUrlTemplate: http://www.youtube.com/v/%1$s&rel=1
        thumbnailUrlTemplate: http://i.ytimg.com/vi/%1$s/default.jpg
        extractPattern: /youtube\.com\/(v\/|watch\?v=)([\w\-]+)/
        apiUrl: http://www.youtube.com/api2_rest
        defaultWidth: 425
        defaultHeight: 350
    google:
        urlPattern: video.google
        extractPattern: /docid=([^&]*)/i
        embedUrlTemplate: http://video.google.com/googleplayer.swf?docId=%1$s
        thumbnailUrlTemplate: http://video.google.com/videofeed?docid=%s
        thumbnailUrlExtractPattern: '/<media:thumbnail url="([^"]+)/'
        defaultWidth: 400
        defaultHeight: 326
    dailymotion:
        urlPattern: dailymotion.com
        embedUrlTemplate: http://www.dailymotion.com/swf/%1$s/
        thumbnailUrlTemplate: http://www.dailymotion.com/thumbnail/160x120/video/%1$s/
        extractPattern: '#/video/([a-zA-Z0-9]+)[^a-zA-Z0-9]#'
        defaultWidth: 425
        defaultHeight: 350         
`


The code referes to a debug function, you can use:

`function debug_log($msg, $file = "debug")
{
    $dbg = "";
    if (SITE != '[PROD]') {
        $bts = debug_backtrace();
        foreach($bts as $bt) {
            $path = str_replace(SITEBASE, '', $bt ['file']);
            $dbg .= $path . " line " . $bt['line'] . " (function " . $bt['function'] . ")\n";
        }
        $handle = fopen(SITEBASE . "/../log/{$file}.log", "a");
        fwrite($handle, strftime("%Y-%m-%d %H:%M:%S  ") . $dbg . $msg . "\n------------------\n");
        fclose($handle);
    }
}
`
