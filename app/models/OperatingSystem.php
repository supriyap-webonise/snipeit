<?php
class OperatingSystem extends Elegant
{
    protected $softDelete = true;
    // Declare the rules for the form validation

    public function assets()
    {
        return $this->hasMany('Asset', 'os_id');
    }

    public function num_assets()
    {
        return $this->hasMany('Asset', 'os_id')->count();
    }

    public function addhttp($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
    return $url;
    }
}
