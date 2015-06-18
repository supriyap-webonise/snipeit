<?php

class Asset extends Elegant
{
    protected $table = 'assets';
    protected $softDelete = true;

    protected $errors;
    protected $rules = array(
        'name'   			=> 'alpha_space|min:2|max:255',
        'model_id'   		=> 'required',
        'operating_system'  => 'required',
        'warranty_months'   => 'integer|min:0|max:240',
        'note'   			=> 'alpha_space',
        'notes'   			=> 'alpha_space',
        'pysical' 			=> 'integer',
        'supplier_id' 		=> 'integer',
        //'asset_tag'   => 'required|alpha_space|min:3|max:255|unique:assets,asset_tag,{id},deleted_at,NULL',
        //'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
        //'asset_tag' => 'required|alpha_space|min:2|max:255|unique:assets,asset_tag,deleted_at,NULL',
        'asset_tag'   => 'required|alpha_space|min:3|max:255|unique:assets,asset_tag,{id}',
        'serial'   			=> 'required|alpha_dash|min:3|max:255|unique:assets,serial,{id}',
        'status' 			=> 'integer'
        );

    /**
    * Handle depreciation
    */
    public function depreciate()
    {
        return $this->getCurrentValue(
            Model::find($this->model_id)->depreciation_id,
            $this->purchase_cost,
            $this->purchase_date
        );
    }

    public function assigneduser()
    {
        return $this->belongsTo('User', 'assigned_to');
    }

    /**
    * Get the asset's location based on the assigned user
    **/
    public function assetloc()
    {
        return $this->assigneduser->userloc();
    }


    /**
    * Get the asset's location based on the assigned user
    **/
    public function defaultLoc()
    {
        return $this->hasOne('Location', 'id', 'rtd_location_id');
    }


    /**
    * Get action logs for this asset
    */
    public function assetlog()
    {
        return $this->hasMany('Actionlog','asset_id')->where('asset_type','=','hardware')->orderBy('added_on', 'desc')->withTrashed();
    }

    /**
    * Get action logs for this asset
    */
    public function adminuser()
    {
        return $this->belongsTo('User','user_id');
    }

    /**
    * Get total assets
    */
     public static function assetcount()
    {
        return DB::table('assets')
                    ->where('physical', '=', '1')
                    ->whereNull('deleted_at','and')
                    ->count();
    }

    /**
    * Get total assets not checked out
    */
     public static function availassetcount()
    {
        return Asset::orderBy('asset_tag', 'ASC')->where('status_id', '=', 0)->where('assigned_to','=','0')->where('physical', '=', 1)->count();
    }

    /**
    * Get total assets
    */
     public function assetstatus()
    {
        return $this->belongsTo('Statuslabel','status_id');
    }


     public function warrantee_expires()
    {
            $date = date_create($this->purchase_date);
            date_add($date, date_interval_create_from_date_string($this->warranty_months.' months'));
            return date_format($date, 'Y-m-d');
    }

     public function months_until_depreciated()
    {
            $today = date("Y-m-d");

            // @link http://www.php.net/manual/en/class.datetime.php
            $d1 = new DateTime($today);
            $d2 = new DateTime($this->depreciated_date());

            // @link http://www.php.net/manual/en/class.dateinterval.php
            $interval = $d1->diff($d2);
            return $interval;
    }


     public function depreciated_date()
    {
            $date = date_create($this->purchase_date);
            date_add($date, date_interval_create_from_date_string($this->depreciation->months.' months'));
            return date_format($date, 'Y-m-d');
    }


    public function depreciation()
    {
        return $this->model->belongsTo('Depreciation','depreciation_id');
    }

    public function model()
    {
        return $this->belongsTo('Model','model_id');
    }

	/**
	* Get the license seat information
	**/
     public function licenses()
    {
       	return $this->belongsToMany('License', 'license_seats', 'asset_id', 'license_id');

    }

     public function licenseseats()
    {
    		return $this->hasMany('LicenseSeat', 'asset_id');
    }


    public function supplier()
    {
        return $this->belongsTo('Supplier','supplier_id');
    }

    public function months_until_eol()
    {
            $today = date("Y-m-d");
            $d1 = new DateTime($today);
            $d2 = new DateTime($this->eol_date());

            if ($this->eol_date() > $today) {
                $interval = $d2->diff($d1);
            } else {
                $interval = NULL;
            }

            return $interval;
    }

    public function eol_date()
    {
            $date = date_create($this->purchase_date);
            date_add($date, date_interval_create_from_date_string($this->model->eol.' months'));
            return date_format($date, 'Y-m-d');
    }


    /**
    * Get total assets
    */
     public static function autoincrement_asset()
    {
        $settings = Setting::getSettings();
		if ($settings->auto_increment_assets == '1') {
			$asset_tag = DB::table('assets')
                    ->where('physical', '=', '1')
                    ->orderBy('created_at','desc')
                    ->first();
			return $settings->auto_increment_prefix.($asset_tag->id + 1);
		} else {
			return false;
		}
    }
}
