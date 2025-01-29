<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

class Image
{
    /**
     * @var string
     */
    private $path;
    
    /**
     * @var string
     */
    private $url;
    
    /**
     * @var ?string
     */
    private $srcSet = null;

    /**
     * @param string $path
     * @param string $url
     * @param ?string $srcSet
     */
    public function __construct(
        string $path,
        string $url,
        ?string $srcSet = null
    ) {
        $this->path = $path;
        $this->url = $url;
        $this->srcSet = $srcSet;
    }
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    
    /**
     * @return ?string
     */
    public function getSrcSet(): ?string
    {
        return $this->srcSet;
    }
    
    /**
     * @param ?string
     */
    public function setSrcSet(?string $srcSet): void
    {
        $this->srcSet = $srcSet;
    }
    
    /**
     * @return string
     */
    public function getMimetype(): string
    {
        return 'image/' . $this->getCode();
    }
    
    /**
     * @return string
     */
    public function getCode(): string
    {
        if (preg_match('/.gif$/i', $this->getPath())) {
            return 'gif';
        }
        
        if (preg_match('/.(jpeg|jpg)$/i', $this->getPath())) {
            return 'jpeg';
        }
        
        if (preg_match('/.webp$/i', $this->getPath())) {
            return 'webp';
        }
        
        return 'png';
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }
}
