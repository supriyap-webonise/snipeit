<?php namespace Controllers\Admin;

use AdminController;
use Input;
use Lang;
use Asset;
use Supplier;
use Statuslabel;
use User;
use Setting;
use Redirect;
use DB;
use Actionlog;
use Model;
use Depreciation;
use Sentry;
use Str;
use Validator;
use View;
use Response;
use Config;
use Location;
use Log;
use OperatingSystem;
use Device;
use Ram;

use BaconQrCode\Renderer\Image as QrImage;

class AssetsController extends AdminController
{
    protected $qrCodeDimensions = array( 'height' => 170, 'width' => 170);

    /**
     * Show a list of all the assets.
     *
     * @return View
     */

    public function getIndex()
    {
        // Grab all the assets

        // Filter results
        if (Input::get('Pending')) {
        	$assets = Asset::with('model','assigneduser','assetstatus','defaultLoc','assetlog')
        	->whereNull('status_id','and')
        	->where('assigned_to','=','0')
        	->where('physical', '=', 1)
        	->get();
        } elseif (Input::get('RTD')) {
        	$assets = Asset::with('model','assigneduser','assetstatus','defaultLoc','assetlog')
        	->where('status_id', '=', 0)
        	->where('assigned_to', '=', '0')
        	->where('physical', '=', 1)
        	->orderBy('asset_tag', 'ASC')
        	->get();
        } elseif (Input::get('Undeployable')) {
        	$assets = Asset::with('model','assigneduser','assetstatus','defaultLoc','assetlog')
        	->where('status_id', '>', 1)
        	->where('physical', '=', 1)
        	->orderBy('asset_tag', 'ASC')
        	->get();
        } elseif (Input::get('Deployed')) {
        	$assets = Asset::with('model','assigneduser','assetstatus','defaultLoc','assetlog')
        	->where('status_id', '=', 0)
        	->where('physical', '=', 1)
        	->where('assigned_to','>','0')
        	->orderBy('asset_tag', 'ASC')
        	->get();
        } else {
        	$assets = Asset::with('model','assigneduser','assetstatus','defaultLoc')
        	->where('physical', '=', 1)
        	->orderBy('asset_tag', 'ASC')
        	->get();
        }

        // Paginate the users
        /**$assets = $assets->paginate(Setting::getSettings()->per_page)
            ->appends(array(
                'Pending' => Input::get('Pending'),
                'RTD' => Input::get('RTD'),
                'Undeployable' => Input::get('Undeployable'),
                'Deployed' => Input::get('Deployed'),
            ));
        **/

        return View::make('backend/hardware/index', compact('assets'));
    }

    /**
     * Asset create.
     *
     * @param null $model_id
     *
     * @return View
     */
    public function getCreate($model_id = null)
    {

        // Grab the dropdown list of models
        //$model_list = array('' => 'Select a Model') + Model::orderBy('name', 'asc')->lists('name'.' '. 'modelno', 'id');

        $model_list = array('' => 'Select a Model') + DB::table('models')
        ->select(DB::raw('concat(name," / ",modelno) as name, id'))->orderBy('name', 'asc')
        ->orderBy('modelno', 'asc')
        ->lists('name', 'id');


        $os_list = array('' => '') + OperatingSystem::orderBy('name', 'asc')->lists('name', 'id');
        $device_list = array('' => '') + Device::orderBy('name', 'asc')->lists('name', 'id');
        $ram_list = array('' => '') + Ram::orderBy('name', 'asc')->lists('name', 'id');
        $supplier_list = array('' => '') + Supplier::orderBy('name', 'asc')->lists('name', 'id');
        $assigned_to = array('' => 'Select a User') + DB::table('users')->select(DB::raw('concat (first_name," ",last_name) as full_name, id'))->whereNull('deleted_at')->lists('full_name', 'id');
        $location_list = array('' => '') + Location::orderBy('name', 'asc')->lists('name', 'id');


        // Grab the dropdown list of status
        $statuslabel_list = array('' => Lang::get('general.pending')) + array('0' => Lang::get('general.ready_to_deploy')) + Statuslabel::orderBy('name', 'asc')->lists('name', 'id');

        $view = View::make('backend/hardware/edit');
        $view->with('os_list',$os_list);
        $view->with('device_list',$device_list);
        $view->with('ram_list',$ram_list);
        $view->with('supplier_list',$supplier_list);
        $view->with('model_list',$model_list);
        $view->with('statuslabel_list',$statuslabel_list);
        $view->with('assigned_to',$assigned_to);
        $view->with('location_list',$location_list);
        $view->with('asset',new Asset);

        if (!is_null($model_id)) {
            $selected_model = Model::find($model_id);
            $view->with('selected_model',$selected_model);
        }

        return $view;
    }

    /**
     * Asset create form processing.
     *
     * @return Redirect
     */
    public function postCreate()
    {
        // create a new model instance
        $asset = new Asset();

        //attempt to validate
        $validator = Validator::make(Input::all(), $asset->validationRules());

        if ($validator->fails())
        {
            // The given data did not pass validation
            return Redirect::back()->withInput()->withErrors($validator->messages());
        }
        // attempt validation
        else {

            if ( e(Input::get('status_id')) == '') {
                $asset->status_id =  NULL;
            } else {
                $asset->status_id = e(Input::get('status_id'));
            }

            if (e(Input::get('warranty_months')) == '') {
                $asset->warranty_months =  NULL;
            } else {
                $asset->warranty_months        = e(Input::get('warranty_months'));
            }

            if (e(Input::get('purchase_cost')) == '') {
                $asset->purchase_cost =  NULL;
            } else {
                $asset->purchase_cost = ParseFloat(e(Input::get('purchase_cost')));
            }

            if (e(Input::get('purchase_date')) == '') {
                $asset->purchase_date =  NULL;
            } else {
                $asset->purchase_date        = e(Input::get('purchase_date'));
            }

            if (e(Input::get('assigned_to')) == '') {
                $asset->assigned_to =  0;
            } else {
                $asset->assigned_to        = e(Input::get('assigned_to'));
            }

            if (e(Input::get('supplier_id')) == '') {
                $asset->supplier_id =  0;
            } else {
                $asset->supplier_id        = e(Input::get('supplier_id'));
            }

            if (e(Input::get('requestable')) == '') {
                $asset->requestable =  0;
            } else {
                $asset->requestable        = e(Input::get('requestable'));
            }

            if ( e(Input::get('device')) == '') {
                $asset->device_id =  NULL;
            } else {
                $asset->device_id = e(Input::get('device'));
            }

            if ( e(Input::get('operating_system')) == '') {
                $asset->os_id =  NULL;
            } else {
                $asset->os_id = e(Input::get('operating_system'));
            }

            if ( e(Input::get('operating_system')) == '') {
                $asset->ram_id =  NULL;
            } else {
                $asset->ram_id = e(Input::get('ram'));
            }
            // Save the asset data
            $asset->name            		= e(Input::get('name'));
            $asset->serial            		= e(Input::get('serial'));
            $asset->model_id           		= e(Input::get('model_id'));
            $asset->order_number            = e(Input::get('order_number'));
            $asset->notes            		= e(Input::get('notes'));
            $asset->asset_tag            	= e(Input::get('asset_tag'));

            $asset->user_id          		= Sentry::getId();
            $asset->archived          			= '0';
            $asset->physical            		= '1';
            $asset->depreciate          		= '0';

            // Was the asset created?
            if($asset->save()) {
                // Redirect to the asset listing page
                return Redirect::to("hardware")->with('success', Lang::get('admin/hardware/message.create.success'));
            }
        }

        // Redirect to the asset create page with an error
        return Redirect::to('assets/create')->with('error', Lang::get('admin/hardware/message.create.error'));


    }

    /**
     * Asset update.
     *
     * @param  int  $assetId
     * @return View
     */
    public function getEdit($assetId = null)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.does_not_exist'));
        }


        // Grab the dropdown list of models
        $model_list = array('' => '') + Model::orderBy('name', 'asc')->lists('name', 'id');
        $supplier_list = array('' => '') + Supplier::orderBy('name', 'asc')->lists('name', 'id');
        $location_list = array('' => '') + Location::orderBy('name', 'asc')->lists('name', 'id');
        $os_list = array('' => '') + OperatingSystem::orderBy('name', 'asc')->lists('name', 'id');
        $device_list = array('' => '') + Device::orderBy('name', 'asc')->lists('name', 'id');
        $ram_list = array('' => '') + Ram::orderBy('name', 'asc')->lists('name', 'id');

        // Grab the dropdown list of status
        $statuslabel_list = array('' => Lang::get('general.pending')) + array('0' => Lang::get('general.ready_to_deploy')) + Statuslabel::orderBy('name', 'asc')->lists('name', 'id');

        return View::make('backend/hardware/edit', compact('asset'))->with('model_list',$model_list)->with('supplier_list',$supplier_list)->with('location_list',$location_list)->with('statuslabel_list',$statuslabel_list)->with('os_list',$os_list)->with('device_list',$device_list)->with('ram_list',$ram_list);
    }


    /**
     * Asset update form processing page.
     *
     * @param  int  $assetId
     * @return Redirect
     */
    public function postEdit($assetId = null)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.does_not_exist'));
        }

        //attempt to validate
        $validator = Validator::make(Input::all(), $asset->validationRules($assetId));

        if ($validator->fails())
        {
            // The given data did not pass validation
            return Redirect::back()->withInput()->withErrors($validator->messages());
        }
        // attempt validation
        else {


            if ( e(Input::get('status_id')) == '' ) {
                $asset->status_id =  NULL;
            } else {
                $asset->status_id = e(Input::get('status_id'));
            }

            if (e(Input::get('warranty_months')) == '') {
                $asset->warranty_months =  NULL;
            } else {
                $asset->warranty_months        = e(Input::get('warranty_months'));
            }

            if (e(Input::get('purchase_cost')) == '') {
                $asset->purchase_cost =  NULL;
            } else {
                $asset->purchase_cost = ParseFloat(e(Input::get('purchase_cost')));
            }

            if (e(Input::get('purchase_date')) == '') {
                $asset->purchase_date =  NULL;
            } else {
                $asset->purchase_date        = e(Input::get('purchase_date'));
            }

            if (e(Input::get('supplier_id')) == '') {
                $asset->supplier_id =  NULL;
            } else {
                $asset->supplier_id        = e(Input::get('supplier_id'));
            }

             if (e(Input::get('requestable')) == '') {
                $asset->requestable =  0;
            } else {
                $asset->requestable        = e(Input::get('requestable'));
            }

            if (e(Input::get('rtd_location_id')) == '') {
                $asset->rtd_location_id = 0;
            } else {
                $asset->rtd_location_id     = e(Input::get('rtd_location_id'));
            }

            if ( e(Input::get('device')) == '') {
                $asset->device_id =  NULL;
            } else {
                $asset->device_id = e(Input::get('device'));
            }

            if ( e(Input::get('operating_system')) == '') {
                $asset->os_id =  NULL;
            } else {
                $asset->os_id = e(Input::get('operating_system'));
            }

            if ( e(Input::get('operating_system')) == '') {
                $asset->ram_id =  NULL;
            } else {
                $asset->ram_id = e(Input::get('ram'));
            }
            // Update the asset data
            $asset->name            		= e(Input::get('name'));
            $asset->serial            		= e(Input::get('serial'));
            $asset->model_id           		= e(Input::get('model_id'));
            $asset->order_number                = e(Input::get('order_number'));
            $asset->asset_tag           	= e(Input::get('asset_tag'));
            $asset->notes            		= e(Input::get('notes'));
            $asset->physical            	= '1';
            // Was the asset updated?
            if($asset->save()) {
                // Redirect to the new asset page
                return Redirect::to("hardware/$assetId/view")->with('success', Lang::get('admin/hardware/message.update.success'));
            }
            else
            {
                 return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.does_not_exist'));
             }
        }


        // Redirect to the asset management page with error
        return Redirect::to("hardware/$assetId/edit")->with('error', Lang::get('admin/hardware/message.update.error'));

    }

    /**
     * Delete the given asset.
     *
     * @param  int  $assetId
     * @return Redirect
     */
    public function getDelete($assetId)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.not_found'));
        }

        if (isset($asset->assigneduser->id) && ($asset->assigneduser->id!=0)) {
            // Redirect to the asset management page
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.assoc_users'));
        } else {
            // Delete the asset
            $asset->delete();

            // Redirect to the asset management page
            return Redirect::to('hardware')->with('success', Lang::get('admin/hardware/message.delete.success'));
        }



    }

    /**
    * Check out the asset to a person
    **/
    public function getCheckout($assetId)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.not_found'));
        }

        // Get the dropdown of users and then pass it to the checkout view
        $users_list = array('' => 'Select a User') + DB::table('users')->select(DB::raw('concat(last_name,", ",first_name) as full_name, id'))->whereNull('deleted_at')->orderBy('last_name', 'asc')->orderBy('first_name', 'asc')->lists('full_name', 'id');

        return View::make('backend/hardware/checkout', compact('asset'))->with('users_list',$users_list);

    }

    /**
    * Check out the asset to a person
    **/
    public function postCheckout($assetId)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.not_found'));
        }

        $assigned_to = e(Input::get('assigned_to'));


        // Declare the rules for the form validation
        $rules = array(
            'assigned_to'   => 'required|min:1',
            'note'   => 'alpha_space',
        );

        // Create a new validator instance from our validation rules
        $validator = Validator::make(Input::all(), $rules);

        // If validation fails, we'll exit the operation now.
        if ($validator->fails()) {
            // Ooops.. something went wrong
            return Redirect::back()->withInput()->withErrors($validator);
        }


        // Check if the user exists
        if (is_null($assigned_to = User::find($assigned_to))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.user_does_not_exist'));
        }

        // Update the asset data
        $asset->assigned_to            		= e(Input::get('assigned_to'));

        // Was the asset updated?
        if($asset->save()) {
            $logaction = new Actionlog();
            $logaction->asset_id = $asset->id;
            $logaction->checkedout_to = $asset->assigned_to;
            $logaction->asset_type = 'hardware';
            $logaction->location_id = $assigned_to->location_id;
            $logaction->user_id = Sentry::getUser()->id;
            $logaction->note = e(Input::get('note'));
            $log = $logaction->logaction('checkout');

            // Redirect to the new asset page
            return Redirect::to("hardware")->with('success', Lang::get('admin/hardware/message.checkout.success'));
        }

        // Redirect to the asset management page with error
        return Redirect::to("hardware/$assetId/checkout")->with('error', Lang::get('admin/hardware/message.checkout.error'));
    }


    /**
    * Check the asset back into inventory
    *
    * @param  int  $assetId
    * @return View
    **/
    public function getCheckin($assetId)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.not_found'));
        }

        return View::make('backend/hardware/checkin', compact('asset'));
    }


    /**
    * Check in the item so that it can be checked out again to someone else
    *
    * @param  int  $assetId
    * @return View
    **/
    public function postCheckin($assetId)
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.not_found'));
        }

        if (!is_null($asset->assigned_to)) {
            $user = User::find($asset->assigned_to);
        }

        $logaction = new Actionlog();
        $logaction->checkedout_to = $asset->assigned_to;

        // Update the asset data to null, since it's being checked in
        $asset->assigned_to            		= '0';

        // Was the asset updated?
        if($asset->save()) {

            $logaction->asset_id = $asset->id;
            $logaction->location_id = NULL;
            $logaction->asset_type = 'hardware';
            $logaction->note = e(Input::get('note'));
            $logaction->user_id = Sentry::getUser()->id;
            $log = $logaction->logaction('checkin from');

            // Redirect to the new asset page
            return Redirect::to("hardware")->with('success', Lang::get('admin/hardware/message.checkin.success'));
        }

        // Redirect to the asset management page with error
        return Redirect::to("hardware")->with('error', Lang::get('admin/hardware/message.checkin.error'));
    }


    /**
    *  Get the asset information to present to the asset view page
    *
    * @param  int  $assetId
    * @return View
    **/
    public function getView($assetId = null)
    {
        $asset = Asset::find($assetId);

        if (isset($asset->id)) {

            $settings = Setting::getSettings();

            $qr_code = (object) array(
                'display' => $settings->qr_code == '1',
                'height' => $this->qrCodeDimensions['height'],
                'width' => $this->qrCodeDimensions['width'],
                'url' => route('qr_code/hardware', $asset->id)
            );

            return View::make('backend/hardware/view', compact('asset', 'qr_code'));
        } else {
            // Prepare the error message
            $error = Lang::get('admin/hardware/message.does_not_exist', compact('id'));

            // Redirect to the user management page
            return Redirect::route('assets')->with('error', $error);
        }

    }

    /**
    *  Get the QR code representing the asset
    *
    * @param  int  $assetId
    * @return View
    **/
    public function getQrCode($assetId = null)
    {
        $settings = Setting::getSettings();

        if ($settings->qr_code == '1') {
            $asset = Asset::find($assetId);
            if (isset($asset->id)) {


                $renderer = new \BaconQrCode\Renderer\Image\Png;
                $renderer->setWidth($this->qrCodeDimensions['height'])
                ->setHeight($this->qrCodeDimensions['height']);

                $writer = new \BaconQrCode\Writer($renderer);
                $content = $writer->writeString(route('view/hardware', $asset->id));

                $content_disposition = sprintf('attachment;filename=qr_code_%s.png', preg_replace('/\W/', '', $asset->asset_tag));
                $response = Response::make($content, 200);
                $response->header('Content-Type', 'image/png');
                $response->header('Content-Disposition', $content_disposition);
                return $response;
            }
        }

        $response = Response::make('', 404);
        return $response;
    }

    /**
     * Asset update.
     *
     * @param  int  $assetId
     * @return View
     */
    public function getClone($assetId = null)
    {
        // Check if the asset exists
        if (is_null($asset_to_clone = Asset::find($assetId))) {
            // Redirect to the asset management page
            return Redirect::to('hardware')->with('error', Lang::get('admin/hardware/message.does_not_exist'));
        }

        // Grab the dropdown list of models
        $model_list = array('' => '') + Model::lists('name', 'id');
        $os_list = array('' => '') + OperatingSystem::orderBy('name', 'asc')->lists('name', 'id');
        $device_list = array('' => '') + Device::orderBy('name', 'asc')->lists('name', 'id');
        $ram_list = array('' => '') + Ram::orderBy('name', 'asc')->lists('name', 'id');
        // Grab the dropdown list of status
        $statuslabel_list = array('' => 'Pending') + array('0' => 'Ready to Deploy') + Statuslabel::lists('name', 'id');

         $location_list = array('' => '') + Location::lists('name', 'id');

        // get depreciation list
        $depreciation_list = array('' => '') + Depreciation::lists('name', 'id');
        $supplier_list = array('' => '') + Supplier::orderBy('name', 'asc')->lists('name', 'id');
        $assigned_to = array('' => 'Select a User') + DB::table('users')->select(DB::raw('concat (first_name," ",last_name) as full_name, id'))->whereNull('deleted_at')->lists('full_name', 'id');

        $asset = clone $asset_to_clone;
        $asset->id = null;
        $asset->asset_tag = '';
        return View::make('backend/hardware/edit')->with('supplier_list',$supplier_list)->with('model_list',$model_list)->with('statuslabel_list',$statuslabel_list)->with('assigned_to',$assigned_to)->with('asset',$asset)->with('location_list',$location_list)->with('os_list',$os_list)->with('device_list',$device_list)->with('ram_list',$ram_list);

    }
}
