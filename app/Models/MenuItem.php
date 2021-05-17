<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    public function getNestedMenuItems($menuId = null) {
        $menuItems = $this->where('parent_id', $menuId)->get()->toArray();
        $tempArray = [];
        foreach ($menuItems as $singleMenu) {
            $singleMenu['children'] = $this->getNestedMenuItems($singleMenu['id']);
            $tempArray[] = $singleMenu;
        }
        $menuItems = $tempArray;
        
        return $menuItems;
    }
}
