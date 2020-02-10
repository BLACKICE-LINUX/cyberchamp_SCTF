<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
	protected $table = 'challenges';
	
	protected $fillable = [
	    'score',
	    'title',
	    'flag',
	    'content'
	];

	public function attachments() 
	{
		return $this->hasOne('App\Attachment');
	}

	public function categories() 
	{
		return $this->hasOne('App\Category', 'category_id', 'id');
	}

	public function challenge_categories()
	{
		return $this->hasMany('App\ChallengeCategory', 'challenge_id', 'id');
	}
}
