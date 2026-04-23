<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class AdFavorite extends Model

{

    protected $fillable = ['ad_id', 'user_id'];



    public function ad()

    {

        return $this->belongsTo(Ad::class);

    }



    public function user()

    {

        return $this->belongsTo(User::class);

    }

}

