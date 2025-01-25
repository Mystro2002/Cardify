<?php

namespace App\Http\Livewire\UserManagement\Roles;

use Livewire\Component;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoleAdd extends Component
{
    public $name;
    public $permission;
    public $role;

    public $searchTerm = ''; // New property for search term

    public $selectAll = false;
    public $notBooted = true;
    public $groupSelectAll = []; // Group select all tracking

    public $selectedPermission = [];
    public $filteredPermissions = [];
    public $allpermissions = [];

    protected $rules = [
        'name' => 'required|unique:roles,name',
        'permission' => 'required'
    ];

    public function mount()
    {
        $this->authorize('role-create');

        $this->loadPermissions();
    }

    // Method to load permissions with optional search functionality
    public function loadPermissions()
    {
        $this->permission = Permission::query()
            ->when($this->searchTerm, function ($query) {
                // Search filter logic
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

    // Trigger search when the search term is updated
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

        $this->selectAll = collect($this->groupSelectAll)->every(function ($value) {
            return $value === true;
        });
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $validatedData = $this->validate([
            'name' => 'required|unique:roles,name',
            'selectedPermission' => 'required'
        ]);

        $this->dispatch('saved');

        foreach ($validatedData['selectedPermission'] as $key => $value) {
            if ($value == false) {
                unset($validatedData['selectedPermission'][$key]);
            }
        }

        $role = Role::create(['name' => $validatedData['name']]);
        $role->syncPermissions(array_keys($validatedData['selectedPermission']));

        return redirect()->route('roles')
            ->with('success', 'Role created successfully.');
    }

    public function render()
    {
        $this->dispatch('cardLoaded', true);

        return view('livewire.Users.roles.role-add');
    }
}