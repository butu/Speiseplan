<?php

namespace Bube\Speiseplan\Model;


use JsonSerializable;

class Meal implements JsonSerializable
{
    protected $vegitarian = false;
    protected $soup = false;
    protected $completeTitle = '';
    protected $description = '';
    protected $imageUrl = '';

    /**
     * @return bool
     */
    public function isVegitarian()
    {
        return $this->vegitarian;
    }

    /**
     * @param bool $vegitarian
     * @return Meal
     */
    public function setVegitarian($vegitarian)
    {
        $this->vegitarian = $vegitarian;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSoup()
    {
        return $this->soup;
    }

    /**
     * @param bool $soup
     * @return Meal
     */
    public function setSoup($soup)
    {
        $this->soup = $soup;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompleteTitle()
    {
        return $this->completeTitle;
    }

    /**
     * @param string $completeTitle
     * @return Meal
     */
    public function setCompleteTitle($completeTitle)
    {
        $this->completeTitle = $completeTitle;
        return $this;
    }

    protected function getTitles()
    {
        $titles = [$this->completeTitle];
        $exploder = '';
        $seperationWords = [
            'mit',
            'auf',
            'und',
            'u.'
        ];
        foreach ($seperationWords as $word) {
            if (strpos($this->completeTitle, $word) !== false) {
                $titles = explode($word, $this->completeTitle);
                $exploder = $word;
                break;
            }
        }

        if (isset($titles[1])) {
            $titles[1] = $exploder . ' ' . $titles[1];
        }

        return $titles;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return trim($this->getTitles()[0]);
    }

    /**
     * @return string
     */
    public function getSubtitle()
    {
        if (isset($this->getTitles()[1])) {
            $subtitle = trim($this->getTitles()[1]);
            if ($subtitle) {
                return $subtitle;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Meal
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     * @return Meal
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getPrice()
    {
        if ($this->isSoup()) {
            return 0.5;
        } else {
            return 5;
        }
    }

    public function getFormattedPrice()
    {
        return '€ ' . number_format($this->getPrice(), 2, ',', '.');
    }

    public function getId()
    {
        return md5($this->completeTitle);
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}