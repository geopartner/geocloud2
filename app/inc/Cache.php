<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2024 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\inc;

use app\conf\App;
use Error;
use Exception;
use Phpfastcache\CacheManager;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolInterface;
use Phpfastcache\Drivers\Files\Config as FilesConfig;
use Phpfastcache\Drivers\Redis\Config as RedisConfig;
use Phpfastcache\Drivers\Memcached\Config as MemcachedConfig;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;
use Psr\Cache\InvalidArgumentException;


abstract class Cache
{

    /**
     * @var ExtendedCacheItemPoolInterface
     */
    static public ExtendedCacheItemPoolInterface $instanceCache;

    /**
     * @throws Exception
     */
    static public function setInstance(): void
    {
        $redisConfig = null;
        $memcachedConfig = null;
        if (!empty(App::$param['appCache']["host"])) {
            $split = explode(":", App::$param['appCache']["host"] ?: "127.0.0.1:6379");
            $redisConfig = [
                'host' => $split[0],
                'port' => !empty($split[1]) ? (int)$split[1] : 6379,
                'database' => !empty(App::$param["appCache"]["db"]) ? App::$param["appCache"]["db"] : 0,
                'itemDetailedDate' => true,
                'useStaticItemCaching' => false,
            ];
            $memcachedConfig = [
                'host' => $split[0],
                'port' => !empty($split[1]) ? (int)$split[1] : 11211,
                'itemDetailedDate' => true,
                'useStaticItemCaching' => false,
            ];
        }
        $fileConfig = [
            'securityKey' => "phpfastcache",
            'path' => '/var/www/geocloud2/app',
            'itemDetailedDate' => true,
            'useStaticItemCaching' => false,
        ];

        $cacheType = !empty(App::$param["appCache"]["type"]) ? App::$param["appCache"]["type"] : "files";

        Globals::$cacheTtl = !empty(App::$param["appCache"]["ttl"]) ? App::$param["appCache"]["ttl"] : Globals::$cacheTtl;

        self::$instanceCache = match ($cacheType) {
            "redis" => CacheManager::getInstance('redis', new RedisConfig($redisConfig)),
            "memcached" => CacheManager::getInstance('memcached', new MemcachedConfig($memcachedConfig)),
            default => CacheManager::getInstance('files', new FilesConfig($fileConfig)),
        };
    }

    /**
     * @return array
     */
    static public function clear(): array
    {
        $res = self::$instanceCache->clear();
        return [
            "success" => true,
            "message" => $res
        ];
    }

    static public function deleteItemsByTagsAll(array $tags): void
    {
        self::$instanceCache->deleteItemsByTags($tags, TaggableCacheItemPoolInterface::TAG_STRATEGY_ALL); // V8
    }

    /**
     * @param array $key
     * @throws InvalidArgumentException
     */
    static public function deleteItem(string $key): void
    {
        self::$instanceCache->deleteItem($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    static public function deleteItems(array $keys): void
    {
        self::$instanceCache->deleteItems($keys);
    }


    /**
     * @throws InvalidArgumentException
     */
    static public function deleteByPatterns(array $patterns) : void
    {
        foreach ($patterns as $pattern) {
            $items = self::getAllItems($pattern);
            $keys = [];
            foreach ($items as $key => $item) {
                $keys[] = $key;
            }
            self::deleteItems($keys);
        }
    }

    /**
     * @param string $key
     * @return ExtendedCacheItemInterface|null
     */
    static public function getItem(string $key): ?ExtendedCacheItemInterface
    {
        try {
            $CachedString = self::$instanceCache->getItem($key);
        } catch (PhpfastcacheInvalidArgumentException|Error) {
            $CachedString = null;
        }
        return $CachedString;
    }

    static public function getAllItems(string $pattern): iterable
    {
        try {
            $items = self::$instanceCache->getAllItems($pattern);
        } catch (PhpfastcacheUnsupportedMethodException|Error) {
            $items = null;
        }

        return $items;
    }

    /**
     * @param ExtendedCacheItemInterface $CachedString
     */
    static public function save(ExtendedCacheItemInterface $CachedString): void
    {
        try {
            self::$instanceCache->save($CachedString);
        } catch (Error $exception) {
            error_log($exception->getMessage());
        }
    }

    /**
     * @return array
     */
    static public function getStats(): array
    {
        return (array)self::$instanceCache->getStats();
    }

    /**
     * @return array
     */
    static public function getItemsByTagsAsJsonString(): array
    {
        return self::$instanceCache->getItems();
    }
}