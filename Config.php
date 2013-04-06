<?php
/**
 * class to cache multiple configurationFiles
 * when the project is loaded
 *
 * PHP version 5.3
 *
 * @category ZendCache
 * @package  ZendCache
 * @author   Francisco Marcos <fmarcos83@gmail.com>
 * @license  GNU http://gnu.org
 * @version  SVN: $ Revision: $
 * @date     $ Date: $
 * @link     ZendCache\Config
 * @see      Zend_Cache_Front_File, ZendApp\File\RegexFileScanner
 **/

namespace ZendCache;

use ZendApp\File\RegexFileScanner as RegexFileScanner;

/**
 * ZendCache\Config class
 *
 * searchs and caches project configurationFiles
 *
 * @category Config
 * @package  ZendCache
 * @author   Francisco Marcos <fmarcos83@gmail.com>
 * @license  GNU http://gnu.org
 * @link     default
 **/
class Config
{
    private $_options = array(
        'path' => './',
        'regex'=>'/^.*\.ini$/',
        'cacheId'   => 'config',
        'backendCache' =>'File',
        'backendCacheOptions'=>array('cache_dir'=>'/tmp')
    );

    private $_cache = null;
    private $_configFiles = null;
    private $_properties = array();

    /**
     * takes a configuration to configure the cache backend and a dir
     * to scan for config files
     *
     * @param (array) $options configuration
     *
     * regex => the regex to search for configuration files preg(exp)
     * --------------------------------------------------------------
     * cacheId => the id of cache to hold the configuration
     * --------------------------------------------------------------
     * backendCache => name of the cache backend
     * --------------------------------------------------------------
     * backendCacheOptions => options for the cache backend see
     *                        the corresponding class for more info
     *
     * @return null
     * @author Francisco Marcos <fmarcos83@gmail.com>
     **/
    public function __construct(array $options=array())
    {
        $this->setOptions(array_merge($this->_options, $options));
        $fileScanner = new RegexFileScanner($this->path, $this->regex);
        $feCacheName = 'File';
        $beCacheName = $this->backendCache;
        $this->_configFiles = $fileScanner->search();
        $feCacheOptions = array(
            'master_files' => $this->_configFiles,
            'automatic_serialization' => true,
            'lifetime' => false
        );
        $beCacheOptions = $this->backendCacheOptions;
        $this->_cache = \Zend_Cache::factory(
            $feCacheName,
            $beCacheName,
            $feCacheOptions,
            $beCacheOptions
        );
    }

    /**
     * setter method
     *
     * @param (String) $name     variable name
     * @param (mixed)  $variable the value this object is going to hold
     *
     * @return null
     * @author Francisco Marcos <fmarcos83@gmail.com>
     * @see    http://php.net/manual/es/language.oop5.magic.php
     **/
    public function __set($name, $variable)
    {
        $this->_properties[$name] = $variable;
    }

    /**
     * getter method
     *
     * @param (String) $name variable name
     *
     * @return null
     * @author Francisco Marcos <fmarcos83@gmail.com>
     * @see    http://php.net/manual/es/language.oop5.magic.php
     **/
    public function __get($name)
    {
        return $this->_properties[$name];
    }


    /**
     * passes filescanner and cache options to this cache manager
     *
     * @param (array) $options several configuration files
     *
     * @return null
     * @author Francisco Marcos <fmarcos83@gmail.com>
     **/
    public function setOptions(array $options)
    {
        foreach ($options as $key=>$value) {
            $this->{$key} = $value;
        }
    }

    /**
     * loads a config object according to the configuration files
     * TODO: factory to get Config_Class according to configuration file
     *       only supported ini
     *
     * @return Zend_Config
     * @author Francisco Marcos <fmarcos83@gmail.com>
     **/
    public function get()
    {
        $config = $this->_cache->load($this->cacheId);
        if (!$config) {
            $config = new \Zend_Config(array(), true);
            foreach ($this->_configFiles as $aConfigFile) {
                $config->merge(new \Zend_Config_Ini($aConfigFile));
            }
            $this->_cache->save($config, $this->cacheId);
        }
        return $config;
    }
}
