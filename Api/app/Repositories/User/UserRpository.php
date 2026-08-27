<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Base\BaseRepository;

class UserRpository extends BaseRepository {
    
    protected $modelClass = User::class;
    
    public function getUserByEmail(string $email)
    {   
        return $this
            ->getModel()
            ->newQuery()
            ->where('email', $email)
            ->first();
    }
}