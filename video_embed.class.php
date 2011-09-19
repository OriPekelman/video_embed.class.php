<?php
require_once (SITEBASE . '/include/vendor/yaml/spyc.php');
/**
* Copyright (c) 2008, AF83
*   All rights reserved.
*
*   Redistribution and use in source and binary forms, with or without modification,
*   are permitted provided that the following conditions are met:
*
*   1° Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
*
*   2° Redistributions in binary form must reproduce the above copyright notice,
*   this list of conditions and the following disclaimer in the documentation
*   and/or other materials provided with the distribution.
*
*   3° Neither the name of AF83 nor the names of its contributors may be used
*   to endorse or promote products derived from this software without specific
*   prior written permission.
*
*   THIS SOFTWARE IS PROVIDED BY THE COMPANY AF83 AND CONTRIBUTORS "AS IS"
*   AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
*   THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
*   PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
*   CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
*   EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
*   PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
*   PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
*   OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
*   NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
*   EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* @copyright 2008 AF83 http://dev.af83.com
* @author Ori Pekelman
* @license BSD License (3 Clause) http://www.opensource.org/licenses/bsd-license.php
* @package Video
* @version $Id$
* @access public
* @todo thumbnails should be cached locally
*
* VideoEmbed class  canused to safely and cleanly embed videos from different sources
*
* The different sources are configured in the video_embed.yaml file
*
* NB: The embed code may be also a url
*
* Usage: set the configuration variables (refer to readme for details):
*  VIDEO_EMBED_CONFIG_FILE -- path of video embed config file
*
*                     $videoEmbed = new VideoEmbed($embed); optional width and height may be passed to the constructor
*                      print($videoEmbed->embed);
*                      $videoEmbed->width = 240; // resize
*                      $videoEmbed->height = 120;
*                      print($videoEmbed->embed);
*                      print($videoEmbed->thumb);

*
* This class requires the SpyC library to read the cobnfiguration file. please adjust the path of the include on the first line of this file
* The video services are configured in the configuration file (video_embed.conf.inc), example for youtube google and daily motion:
*/

class VideoEmbed {
    private $_video_embed_conf = array();
    private $_embedSource;
    private $_embed;
    private $_id;
    private $_type;
    private $_thumb;
    private $_url;
    private $_width;
    private $_height;
    private $_readOnly = array ('thumb', 'url', 'id', 'type');
    private $_readWrite = array ('width', 'height', 'embed');

    /**
    * VideoEmbed::__construct() Only embed is mandatory
    *
    * @param mixed $embed
    * @param mixed $width
    * @param mixed $height
    */
    function __construct($embed, $width = null, $height = null)
    {
        // load configuration
        if (!class_exists(Spyc)) {
            throw new exception ('Could not find SpyC library ');
        }
        $this->_video_embed_conf = Spyc::YAMLLoad(SITEBASE . VIDEO_EMBED_CONFIG_FILE);
        if (!$this->_video_embed_conf) {
            debug_log ("Could not read configruation file or config file empty  " . SITE_BASE . VIDEO_EMBED_CONFIG_FILE);
            if (DEBUG) {
                throw new exception ("Could not read configruation file or config file empty " . SITE_BASE . VIDEO_EMBED_CONFIG_FILE);
            }
        }

        if (!$embed) {
            debug_log ('This must be instantiated with a non empty embed code');
            if (DEBUG) {
                throw new exception ('This must be instantiated with a non empty embed code');
            }
        }
        // load arguments
        $this->_embedSource = $embed;
        $this->_width = $width?$width: $this->_video_embed_conf['defaultWidth'];
        $this->_height = $height?$height:$this->_video_embed_conf['defaultHeight'];

        $this->setup();
    }
    /**
    * VideoEmbed::__set() Make some variables read only
    *
    * @param mixed $n
    * @param mixed $val
    * @return
    */
    function __set($n, $val)
    {
        if (in_array($n, $this->_readOnly)) {
            debug_log ("Trying to set a read only property $n $val");
            if (DEBUG) {
                throw new exception ("Trying to set  a read only property" . "$n $val");
            }
            return false;
        } elseif (in_array($n, $this->_readWrite)) {
            if ($n == "embed") {
                $property = '_embedSource';
            } else $property = "_" . $n;

            $this->$property = $val;
            $this->setup(); // recalculate stuff if we changed a RW  property
            return true;
        }
        return false;
    }

    /**
    * VideoEmbed::__get()
    *
    * @param mixed $n
    * @return
    */
    function __get($n)
    {
        if (in_array($n, array_merge($this->_readOnly, $this->_readWrite))) {
            $propertyName = "_$n";
            return $this->$propertyName;
        } else {
            debug_log('Trying to get a non readble property ' . $n);
            if (DEBUG) {
                throw new exception ('Trying to get a non readble property ' . $n);
            }
            return false;
        }
    }

    /**
    * VideoEmbed::setup()
    *
    * @return
    */
    private function setup ()
    {
        if (!$this->video_embed_type()) {
            debug_log ('Could not get embed type :' . $this->_embedSource);
            if (DEBUG) {
                throw new exception ('Could not get embed type :' . $this->_embedSource);
            }
        }
        if (!$this->video_embed_id()) {
            debug_log ('Could not get embed id :' . $this->_embedSource);
            if (DEBUG) {
                throw new exception ('Could not get embed id :' . $this->_embedSource);
            }
        }
        if (!$this->video_embed_url()) {
            debug_log ('Problem generating embed url :' . $this->_embedSource);
            if (DEBUG) {
                throw new exception ('Problem generating embed url :' . $this->_embedSource);
            }
        }
        if (!$this->video_embed_thumb()) {
            debug_log ('Problem generating thumb code :' . $this->_embedSource);
            if (DEBUG) {
                throw new exception ('Problem generatingembed code :' . $this->_embed);
            }
        }
        if (!$this->video_embed_embed()) {
            debug_log ('Problem generating embed code :' . $this->_embedSource);
            if (DEBUG) {
                throw new exception ('Problem generatingembed code :' . $this->_embedSource);
            }
        }
    }

    /**
    * VideoEmbed::video_emebd_type()
    *
    * @param mixed $embed
    * @return
    */
    private function video_embed_type()
    {
        $this->_type = "";
        foreach($this->_video_embed_conf['services'] as $serviceName => $config) {
            if (strpos(strtolower($this->_embedSource), strtolower($config['urlPattern']))) {
                $type = $serviceName;
            }
        }
        if ($type) {
            $this->_type = $type;
            return $this->_type;
        }
        return false;
    }

    /**
    * VideoEmbed::video_embed_id()
    *
    * @return
    */
    private function video_embed_id()
    {
        $this->_id = "";

        if (($this->_type)) {
            $regexp = $this->_video_embed_conf['services'][$this->_type]['extractPattern'];
            preg_match($regexp, $this->_embedSource , $match);

            if ($match[count($match)]-1) $this->_id = $match[count($match)-1];
            return $this->_id;
        }
        return false;
    }

    /**
    * VideoEmbed::video_embed_url()
    *
    * @return
    */
    private function video_embed_url()
    {
        $this->_url = "";
        if ($this->_type) {
            if ($this->_id) $url = sprintf($this->_video_embed_conf['services'][$this->_type]['embedUrlTemplate'], $this->_id);
            if ($url) $this->_url = $url;
            return $this->_url;
        }
        return false;
    }

    /**
    * VideoEmbed::video_embed_thumb()
    *
    * @return
    */
    private function video_embed_thumb()
    {
        $conf = $this->_video_embed_conf['services'][$this->_type]; // just here for readability
        $this->_thumb = "";
        if ($this->_type && $this->_id) {
            if (isset($conf['thumbnailUrlExtractPattern'])) { // if we need to parse the response:
                $thumburl = $this->extractThumbUrl($conf['thumbnailUrlTemplate'], $conf['thumbnailUrlExtractPattern']);
            } else $thumburl = sprintf($conf['thumbnailUrlTemplate'], $this->_id);

            if ($thumburl) $this->_thumb = $thumburl;
            return $this->_thumb;
        }
        return false;
    }

    /**
    * VideoEmbed::video_embed_embed()
    *
    * @param mixed $width
    * @param mixed $height
    * @return
    */
    private function video_embed_embed()
    {
        if ($this->_type) {
            $template = isset($this->_video_embed_conf['services'][$this->_type]['embedTemplate']) ? $this->_video_embed_conf['services'][$this->_type]['embedTemplate']:$this->_video_embed_conf['embedTemplate'];
            if ($template && $this->_url) {
                $width = $this->_width?$this->_width:$this->_video_embed_conf['services'][$video_type]['defaultWidth'];
                $height = $this->_height?$this->_height:$this->_video_embed_conf['services'][$video_type]['defaultHeight'];
                $embed = sprintf($template, $this->_url, $width, $height);
                if ($embed) $this->_embed = $embed;
                return $this->_embed;
            }
        }

        return false;
    }
    /**
    * extractThumbUrl()
    *
    * @param mixed $url
    * @param mixed $videoid
    * @param mixed $extractPattern
    * @return
    */
    private function extractThumbUrl($url, $extractPattern)
    {
        $vrss = file_get_contents(sprintf($url, $this->_id));
        if (!empty($vrss)) {
            preg_match($extractPattern, $vrss, $thumbnail_array);
            $thumbnail = $thumbnail_array[1];
            // Remove amp;
            $thumbnail = str_replace('amp;', '', $thumbnail);
        }

        return $thumbnail;
    }
}

/**
* test_VideoEmbed() test all is well
*
* @return
*/
function test_VideoEmbed()
{
    $embeds[] = '<div><object width="420" height="365"><param name="movie" value="http://www.dailymotion.com/swf/x3wq9a&v3=1&related=0"></param><param name="allowFullScreen" value="true"></param><param name="allowScriptAccess" value="always"></param><embed src="http://www.dailymotion.com/swf/x3wq9a&v3=1&related=0" type="application/x-shockwave-flash" width="420" height="365" allowFullScreen="true" allowScriptAccess="always"></embed></object><br /><b><a href="http://www.dailymotion.com/video/x3wq9a_defi-decorer-les-camions-de-pq_fun">Defi: décorer les camions de PQ</a></b><br /><i>Uploaded by <a href="http://www.dailymotion.com/gonzaguetv">gonzaguetv</a></i></div>';
    $embeds[] = '<object width="425" height="355"><param name="movie" value="http://www.youtube.com/v/1mXh_tyLlAY&rel=1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/1mXh_tyLlAY&rel=1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="355"></embed></object>';
    $embeds[] = '<embed style="width:400px; height:326px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=-1182786924290841590&hl=fr" flashvars=""> </embed>';
    foreach($embeds as $embed) {
        $videoEmbed = new VideoEmbed($embed, 420, 365);
        echo "<h2> Type:</h2>\n";
        print ($videoEmbed->type);
        echo "<h2> ID:</h2>\n";
        print($videoEmbed->id);
        echo "<h2> Video Url:</h2>\n";
        print($videoEmbed->url);
        echo "<h2> Embed:</h2>\n";
        print($videoEmbed->embed);
        echo "<h2> Embed resized:</h2>\n";
        $videoEmbed->width = 240;
        $videoEmbed->height = 120;
        print($videoEmbed->embed);
        echo "<h2> thumb url:</h2>\n";
        print($videoEmbed->thumb);
        echo "<h2> thumb image:</h2>\n";
        print('<img src="' . $videoEmbed->thumb . '" />');
        // this should fail
        // $videoEmbed->thumb = "qsdqsdsq";
        // this should fail
        // print('<img src="' . $videoEmbed->image . '" />');
    }
}
