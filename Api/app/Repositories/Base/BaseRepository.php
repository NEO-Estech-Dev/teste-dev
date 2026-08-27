<?php

namespace App\Repositories\Base;

class BaseRepository 
{
    protected $modelClass;
    
    protected function setModel(string $modelClass) 
    { 
        $this->modelClass = $modelClass;
        return app($this->modelClass);
    }

    protected function getModel()
    {
        return app($this->modelClass);
    }

    public function findById(int $id)
    {  
        return $this->newQuery()->find($id);
    }

    public function getAll()
    {  
        return $this->newQuery()->get();
    }

    public function create(array $data)
    {
        return $this->newQuery()->create($data);
    }

    public function insert(array $data)
    {
        return $this->newQuery()->insert($data);
    }

    public function update(Object $objectModel, array $inputData)
    {
        return $objectModel->update($inputData);
    }

    public function destroy(int $id)
    {   
        return $this->getModel()->destroy($id);
    }

    public function newQuery()
    {   
        return $this->getModel()->newQuery();
    }
}