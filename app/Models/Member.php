<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'membership_number',
        'contact_name',
        'company',
        'address_type_a',
        'address1_a',
        'address2_a',
        'area_a',
        'city_a',
        'state_a',
        'pincode_a',
        'address_type_b',
        'address1_b',
        'address2_b',
        'area_b',
        'city_b',
        'state_b',
        'pincode_b',
        'office_phone',
        'home_phone',
        'cell_no',
        'email',
        'web',
        'post',
        'type',
        'dob',
        'aadhar_no',
        'pan_no',
        'gst_no',
        'nature_of_business',
        'business_segment',
        'turn_over',
        'scale_of_business',
        'spouse_name',
        'spouse_phone_no',
        'blood_group',
    ];
}
