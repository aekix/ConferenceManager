<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Task
{
    protected $description;

    protected $votes;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getVotes()
    {
        return $this->votes;
    }
}