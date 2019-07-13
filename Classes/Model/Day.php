<?php

namespace Bube\Speiseplan\Model;


use JsonSerializable;

class Day implements JsonSerializable
{

    /**
     * @var \DateTime
     */
    protected $date = null;

    protected $meals = [];

    protected $title = '';
    protected $tomorrow = null;
    protected $today = null;
    protected $formattedDate = null;


    public function __construct($dayName = null, $date = null)
    {
        $this->title = $dayName;
        $this->date = $date;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return Day
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return array
     */
    public function getMeals()
    {
        return $this->meals;
    }

    /**
     * @param array $meals
     * @return Day
     */
    public function setMeals($meals)
    {
        $this->meals = $meals;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Day
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getFormattedDate()
    {
        return $this->date->format('d.m.Y');
    }

    public function addMeal($meal)
    {
        $this->meals[] = $meal;
    }

    public function appendMeal($meal)
    {
        $this->addMeal($meal);
    }

    public function prependMeal($meal)
    {
        array_unshift($this->meals, $meal);
    }

    public function isOld()
    {
        return $this->date->setTime(13, 00) < new \DateTime('now');
    }

    public function isToday()
    {
        $today = new \DateTime('today');
        $today->setTime(0, 0);
        return $this->date->format('d.m.Y') === $today->format('d.m.Y');
    }

    public function isTomorrow()
    {
        $today = new \DateTime('tomorrow');
        return $this->date->format('d.m.Y') === $today->format('d.m.Y');
    }

    public function jsonSerialize()
    {
        $this->tomorrow = $this->isTomorrow();
        $this->today = $this->isTomorrow();
        $this->formattedDate = $this->getFormattedDate();
        return get_object_vars($this);
    }
}