<?php

namespace Bube\Speiseplan\Controller;

use Bube\Speiseplan\Model\Day;
use Bube\Speiseplan\Model\Meal;
use Cache;
use DOMDocument;
use odannyc\GoogleImageSearch\ImageSearch;
use RtfHtml;
use RtfReader;
use TYPO3Fluid\Fluid\Core\Cache\SimpleFileCache;
use TYPO3Fluid\Fluid\View\TemplateView;

class MealPlanController
{
    const TIMESPAN = 1;
    const MEAL = 2;
    const DAY = 3;

    protected $trashLines = [
        'Buder Buder Buder Buder Buder Buder Buder Buder Buder',
        'Speiseplan',
        'diese Speisen werden fett-, kalorienarm und schonend zubereitet',
    ];

    protected $deletePhrases = [
        'X:',
        'Suppe:',
        ':',
        '     ',
        'Feiertag',
        'vegi'
    ];
    protected $blankPhrases = [
        'M 1',
        'M 2',
    ];
    protected $dayNames = [
        'Montag',
        'Dienstag',
        'Mittwoch',
        'Donnerstag',
        'Freitag'
    ];

    /**
     * @var Cache
     */
    protected $cache = null;

    protected $cachedImages = [];
    protected $blockedImages = [];

    public function __construct()
    {
        // setup google image search
//        ImageSearch::config()->apiKey('AIzaSyDv97UVYwSDwKi1DM1cy_cxiBx5fzMdQPo');
        ImageSearch::config()->apiKey('AIzaSyB95FccqAljB4yLn3S16wy5R2n0NnNEE7Q');
        ImageSearch::config()->cx('009999062285387820005:marqrkxghuc');

        // setup cache
        $this->cache = new Cache();
        $this->cachedImages = $this->cache->retrieve('images');
        $this->blockedImages = $this->cache->retrieve('blockedimages');

    }

    public function listAction()
    {
        $files = [
            #'Speiseplan KW 22-23 Eybl.rtf',
            'Speiseplan KW 26-27 Eybl.rtf'
        ];
        #$days = null;
        $days = $this->cache->retrieve('days');
        if ($days === null) {
            $lines = [];
            foreach ($files as $file) {
                $lines = array_merge($lines, $this->parseRtf($file));
            }
            $days = $this->buildDays($lines);
            $this->cache->store('days', $days);
        }

        return $this->renderView([
            'days' => $days
        ]);
    }

    public function blockImageAction($imageSrc)
    {
        $this->blockedImages[$imageSrc] = $imageSrc;
        foreach ($this->cachedImages as $meal => $url) {
            if ($imageSrc === $url) {
                unset($this->cachedImages[$meal]);
            }
        }
        $this->cache->store('blockedimages', $this->blockedImages);
        $this->cache->store('images', $this->cachedImages);
        $this->cache->store('days', null);
        header('location: /');
    }

    public function changeImageAction($mealTitle, $isSoup, $imageSrc)
    {
        $this->getImageUrl($mealTitle, $isSoup);
        foreach ($this->cachedImages as $meal => $url) {
            if ($imageSrc === $url) {
                $this->cachedImages[$meal] = $imageSrc;
            }
        }
        $this->cache->store('images', $this->cachedImages);
        $this->cache->store('days', null);
        header('location: /');
    }

    protected function parseRtf($fileName)
    {
        // load rtf
        $reader = new RtfReader();
        $rtf = file_get_contents('Resources/Private/RawData/' . $fileName);
        $reader->Parse($rtf);

        // convert rtf to html and parse html
        $formatter = new RtfHtml();
        $dom = new DOMDocument();
        $dom->loadHTML($formatter->Format($reader->root));

        $lines = [];
        foreach ($dom->getElementsByTagName('p') as $node) {
            $line = strip_tags($dom->saveHTML($node));
            $line = trim(preg_replace('/\s\s+/', ' ', $line));
            $lines[] = $line;
        }
        return $lines;
    }

    protected static function strposa($haystack, $needles = array(), $offset = 0)
    {
        $chr = array();
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle, $offset);
            if ($res !== false) {
                $chr[$needle] = $res;
            }
        }
        if (empty($chr)) {
            return false;
        }
        return min($chr);
    }

    /**
     * @param array $lines
     * @return array
     */
    private function buildDays($lines)
    {
        $currentDay = null;
        $days = [];
        $mealCounter = 0;
        foreach ($lines as $dirtyLine) {
            $cleanLine = $this->getCleanLine($dirtyLine);
            $type = $this->getLineType($cleanLine);
            // only continue when this line has real content
            if ($type) {
                switch ($type) {
                    case self::TIMESPAN:
                        // if this is a timespan line, begin to count the days
                        $dateParts = explode('–', str_replace([date('Y'), ' '], '', $cleanLine));
                        $currentDay = new \DateTime($dateParts[0] . date('Y'));
                        break;
                    case self::MEAL:
                        // if this is a meal line
                        $meal = new Meal();
                        $meal->setCompleteTitle($cleanLine);
                        $meal->setVegitarian(strpos($dirtyLine, 'vegi') !== false);
                        $meal->setSoup($mealCounter === 1);
                        $meal->setImageUrl($this->getImageUrl($meal->getTitle(), $meal->isSoup()));
                        $day = $days[$currentDay->getTimestamp()];

                        if ($meal->isSoup()) {
                            $day->prependMeal($meal);
                        } else {
                            $day->appendMeal($meal);
                        }

                        $mealCounter++;
                        break;
                    case self::DAY:
                        // if we already had a timespan line, and this day is not monday, then add another day
                        if ($currentDay && strpos($dirtyLine, $this->dayNames[0]) === false) {
                            $currentDay->modify('+1day');
                        }
                        // and create a new day in the days array
                        $days[$currentDay->getTimestamp()] = new Day($cleanLine, clone $currentDay);
                        $mealCounter = 0;
                        break;
                    default:
                        break;
                }

            }
        }

        $this->cache->store('images', $this->cachedImages);
        return $days;
    }

    private function renderView($variables)
    {
        // Define a cache directory if one is not set
        $FLUID_CACHE_DIRECTORY = !isset($FLUID_CACHE_DIRECTORY) ? __DIR__ . '/../../Resources/cache/' : $FLUID_CACHE_DIRECTORY;
        $view = new TemplateView();
        $paths = $view->getTemplatePaths();
        $paths->setTemplateRootPaths([
            __DIR__ . '/../../Resources/Private/Templates/'
        ]);
        $paths->setLayoutRootPaths([
            __DIR__ . '/../../Resources/Private/Layouts/'
        ]);
        $paths->setPartialRootPaths([
            __DIR__ . '/../../Resources/Private/Partials/'
        ]);
        if ($FLUID_CACHE_DIRECTORY) {
            $view->setCache(new SimpleFileCache($FLUID_CACHE_DIRECTORY));
        }
        $paths->setTemplatePathAndFilename(__DIR__ . '/../../Resources/Private/Templates/List.html');
        $view->assignMultiple($variables);

        return $view->render();
    }

    private function getLineType($line)
    {
        $type = self::MEAL;

        // if the line has a year, then it's the timespan line
        if (strpos($line, date('Y')) !== false) {
            return self::TIMESPAN;
        }

        // if it has a week day name, it's a day
        if (self::strposa($line, $this->dayNames) !== false) {
            return self::DAY;
        }

        // if it is a line full of trash or empty it has no type
        if (empty($line) || in_array($line, $this->trashLines, true)) {
            return null;
        }

        return $type;
    }

    private function getCleanLine($line)
    {
        $line = str_replace($this->deletePhrases, '', $line);
        $line = str_replace($this->blankPhrases, ' ', $line);
        $line = preg_replace('(([ACMHGLFDO][,]){1,}[ACMHGLFDO]{0,1})', '', $line);
        $line = str_replace(chr(194), '', $line);
        $line = str_replace(chr(160), '', $line);
        $line = trim($line);
        return $line;
    }

    /**
     * @param $mealTitle
     * @param $isSoup
     * @return string
     */
    private function getImageUrl($mealTitle, $isSoup)
    {
        $mealHash = md5($mealTitle);
        $url = null;
        $found = false;
        if (!isset($this->cachedImages[$mealHash])) {
            $searchValue = $mealTitle;
            if ($isSoup) {
                $searchValue = str_replace('suppe', '', strtolower($searchValue));
                $searchValue .= 'suppe';
            }
            try {
                $images = ImageSearch::search($searchValue);
                if (isset($images['items'])) {
                    foreach ($images['items'] as $item) {
                        if (!isset($this->blockedImages[$item['link']])) {
                            $url = $item['link'];
                            $this->cachedImages[$mealHash] = $url;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $this->cachedImages[$mealHash] = $images['items'][0]['link'];
                    }
                } else {
                    if ($isSoup) {
                        $searchValue = str_replace('suppe', ' suppe', strtolower($searchValue));
                        $images = ImageSearch::search($searchValue);
                        if (isset($images['items'])) {
                            foreach ($images['items'] as $item) {
                                if (!isset($this->blockedImages[$item['link']])) {
                                    $url = $item['link'];
                                    $this->cachedImages[$mealHash] = $url;
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $this->cachedImages[$mealHash] = $images['items'][0]['link'];
                            }
                        }
                    }
                }
                if (!isset($this->cachedImages[$mealHash])) {
                    $this->cachedImages[$mealHash] = '';
                }
            } catch (\Exception $e) {
            }
        } else {
            $url = $this->cachedImages[$mealHash];
        }
        return $url;
    }

    public function apiAction()
    {
        $files = [
            'Speiseplan KW 12-13 Eybl.rtf'
        ];

        $days = $this->cache->retrieve('days');
        if ($days === null) {
            $lines = [];
            foreach ($files as $file) {
                $lines = array_merge($lines, $this->parseRtf($file));
            }
            $days = $this->buildDays($lines);
            $this->cache->store('days', $days);
        }

        header('Content-Type: application/json');
        return json_encode($days);
    }
}