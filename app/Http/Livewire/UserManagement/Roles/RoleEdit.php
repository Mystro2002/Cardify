<?php

namespace App\Http\Livewire\UserManagement\Roles;

use Livewire\Component;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoleEdit extends Component
{
    use AuthorizesRequests;

    public $roleId;
    public $name;
    public $permission;
    public $role;

    public $searchTerm = '';

    public $selectAll = false;
    public $notBooted = true;
    public $groupSelectAll = [];

    public $selectedPermission = [];
    public $filteredPermissions = [];
    public $allpermissions = [];

    protected $rules = [
        'name' => 'required|unique:roles,name',
        'permission' => 'required'
    ];

    public function mount($id)
    {
        $this->authorize('role-edit');

        $this->roleId = $id;
        $this->role = Role::findOrFail($id);
        $this->name = $this->role->name;

        $this->loadPermissions();
        $this->initializeSelectedPermissions();
    }

    public function loadPermissions()
    {
        $this->permission = Permission::query()
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->get();

        $tempobj = [];
        foreach($this->permission as $key => $p)
        {
            $permissionName = explode("-", $p->name);
            $permHead = $permissionName[0];

            if((count($tempobj) > 0 && array_keys($tempobj)[count($tempobj)-1] != $permHead) || $key == 0)
            {
                $tempobj[$permHead] = [];
            }
            $p->permissionSubName = $permissionName[1];
            array_push($tempobj[$permHead], $p);
        }

        $this->filteredPermissions = $tempobj;

        $this->allpermissions = DB::table('role_has_permissions')
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
    }

    public function initializeSelectedPermissions()
    {
        $rolePermissions = $this->role->permissions->pluck('id')->toArray();
        foreach ($this->permission as $p) {
            $this->selectedPermission[$p->id] = in_array($p->id, $rolePermissions);
        }
        $this->updateGroupSelectAll();
    }

    public function updatedSearchTerm()
    {
        $this->loadPermissions();
    }

    public function selectAllPermissions()
    {
        if ($this->selectAll) {
            foreach ($this->filteredPermissions as $groupName => $permissions) {
                $this->groupSelectAll[$groupName] = true;
                foreach ($permissions as $permission) {
                    $this->selectedPermission[$permission->id] = true;
                }
            }
        } else {
            $this->selectedPermission = [];
            foreach ($this->filteredPermissions as $groupName => $permissions) {
                $this->groupSelectAll[$groupName] = false;
            }
        }
    }

    public function selectGroupPermissions($groupName)
    {
        $selectAll = $this->groupSelectAll[$groupName] ?? false;

        foreach ($this->filteredPermissions[$groupName] as $permission) {
            $this->selectedPermission[$permission->id] = $selectAll;
        }

        $this->updateSelectAll();
    }

    public function updateSelectAll()
    {
        $this->selectAll = collect($this->groupSelectAll)->every(function ($value) {
            return $value === true;
        });
    }

    public function updateGroupSelectAll()
    {
        foreach ($this->filteredPermissions as $groupName => $permissions) {
            $this->groupSelectAll[$groupName] = collect($permissions)->every(function ($permission) {
                return isset($this->selectedPermission[$permission->id]) && $this->selectedPermission[$permission->id];
            });
        }
        $this->updateSelectAll();
    }

    public function update()
    {
        $this->dispatch('scrollToElement');

        $this->validate([
            'name' => 'required|unique:roles,name,' . $this->roleId,
            'selectedPermission' => 'required'
        ]);

        $this->dispatch('saved');

        $selectedPermissions = array_keys(array_filter($this->selectedPermission));

        $this->role->update(['name' => $this->name]);
        $this->role->syncPermissions($selectedPermissions);

        return redirect()->route('roles')
            ->with('success', 'Role updated successfully.');
    }

    public function render()
    {
        $this->dispatch('cardLoaded', true);

        return view('livewire.Users.roles.role-edit');
    }
}