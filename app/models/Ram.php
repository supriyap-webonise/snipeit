<?php
class Ram extends Elegant
{
    protected $table = 'ram';
    protected $softDelete = true;
    // Declare the rules for the form validation

    public function assets()
    {
        return $this->hasMany('Asset', 'ram_id');
    }

    public function num_assets()
    {
        return $this->hasMany('Asset', 'ram_id')->count();
    }

    public function addhttp($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
    return $url;
    }
}
