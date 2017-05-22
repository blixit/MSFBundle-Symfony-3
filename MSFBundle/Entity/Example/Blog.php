<?php

namespace Blixit\MSFBundle\Entity\Example;

use Doctrine\ORM\Mapping as ORM;

/**
 * Blog
 *
 * @ORM\Table(name="example_blog")
 * @ORM\Entity(repositoryClass="Blixit\MSFBundle\Repository\Example\BlogRepository")
 */
class Blog
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=255)
     */
    private $theme;

    /**
     * @var string
     *
     * @ORM\Column(name="authorPseudo", type="string", length=255)
     */
    private $authorPseudo;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set theme
     *
     * @param string $theme
     *
     * @return Blog
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set authorPseudo
     *
     * @param string $authorPseudo
     *
     * @return Blog
     */
    public function setAuthorPseudo($authorPseudo)
    {
        $this->authorPseudo = $authorPseudo;

        return $this;
    }

    /**
     * Get authorPseudo
     *
     * @return string
     */
    public function getAuthorPseudo()
    {
        return $this->authorPseudo;
    }
}

