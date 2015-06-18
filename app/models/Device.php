<?php
class Device extends Elegant
{
    protected $softDelete = true;
    // Declare the rules for the form validation

    public function assets()
    {
        return $this->hasMany('Asset', 'device_id');
    }

    public function num_assets()
    {
        return $this->hasMany('Asset', 'device_id')->count();
    }

    public function addhttp($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
    return $url;
    }
}
