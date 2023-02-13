<?php

namespace Wallo\FilamentCompanies\Http\Livewire;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;
use Wallo\FilamentCompanies\Actions\UpdateCompanyEmployeeRole;
use Wallo\FilamentCompanies\Contracts\AddsCompanyEmployees;
use Wallo\FilamentCompanies\Contracts\InvitesCompanyEmployees;
use Wallo\FilamentCompanies\Contracts\RemovesCompanyEmployees;
use Wallo\FilamentCompanies\Features;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Role;
use Livewire\Component;
use Livewire\Redirector;


/**
 * @property mixed $user
 */
class CompanyEmployeeManager extends Component
{
    /**
     * The company instance.
     *
     * @var mixed
     */
    public $company;

    /**
     * Indicates if a user's role is currently being managed.
     *
     * @var bool
     */
    public $currentlyManagingRole = false;

    /**
     * The user that is having their role managed.
     *
     * @var mixed
     */
    public $managingRoleFor;

    /**
     * The current role for the user that is having their role managed.
     *
     * @var string
     */
    public $currentRole;

    /**
     * Indicates if the application is confirming if a user wishes to leave the current company.
     *
     * @var bool
     */
    public $confirmingLeavingCompany = false;

    /**
     * Indicates if the application is confirming if a company employee should be removed.
     *
     * @var bool
     */
    public $confirmingCompanyEmployeeRemoval = false;

    /**
     * The ID of the company employee being removed.
     *
     * @var int|null
     */
    public $companyEmployeeIdBeingRemoved = null;

    /**
     * The "add company employee" form state.
     *
     * @var array
     */
    public $addCompanyEmployeeForm = [
        'email' => '',
        'role' => null,
    ];

    /**
     * Mount the component.
     *
     * @param  mixed  $company
     * @return void
     */
    public function mount(mixed $company): void
    {
        $this->company = $company;
    }

    /**
     * Add a new company employee to a company.
     *
     * @return void
     */
    public function addCompanyEmployee(): void
    {
        $this->resetErrorBag();

        if (Features::sendsCompanyInvitations()) {
            app(InvitesCompanyEmployees::class)->invite(
                $this->user,
                $this->company,
                $this->addCompanyEmployeeForm['email'],
                $this->addCompanyEmployeeForm['role']
            );
        } else {
            app(AddsCompanyEmployees::class)->add(
                $this->user,
                $this->company,
                $this->addCompanyEmployeeForm['email'],
                $this->addCompanyEmployeeForm['role']
            );
        }

        $this->addCompanyEmployeeForm = [
            'email' => '',
            'role' => null,
        ];

        $this->company = $this->company->fresh();

        $this->emit('saved');
    }

    /**
     * Cancel a pending company employee invitation.
     *
     * @param int $invitationId
     * @return void
     */
    public function cancelCompanyInvitation(int $invitationId): void
    {
        if (! empty($invitationId)) {
            $model = FilamentCompanies::companyInvitationModel();

            $model::whereKey($invitationId)->delete();
        }

        $this->company = $this->company->fresh();
    }

    /**
     * Allow the given user's role to be managed.
     *
     * @param int $userId
     * @return void
     */
    public function manageRole(int $userId): void
    {
        $this->currentlyManagingRole = true;
        $this->managingRoleFor = FilamentCompanies::findUserByIdOrFail($userId);
        $this->currentRole = $this->managingRoleFor->companyRole($this->company)->key;
    }

    /**
     * Save the role for the user being managed.
     *
     * @param UpdateCompanyEmployeeRole $updater
     * @return void
     * @throws AuthorizationException
     */
    public function updateRole(UpdateCompanyEmployeeRole $updater): void
    {
        $updater->update(
            $this->user,
            $this->company,
            $this->managingRoleFor->id,
            $this->currentRole
        );

        $this->company = $this->company->fresh();

        $this->stopManagingRole();
    }

    /**
     * Stop managing the role of a given user.
     *
     * @return void
     */
    public function stopManagingRole(): void
    {
        $this->currentlyManagingRole = false;
    }

    /**
     * Remove the currently authenticated user from the company.
     *
     * @param RemovesCompanyEmployees $remover
     * @return RedirectResponse|Redirector
     */
    public function leaveCompany(RemovesCompanyEmployees $remover): RedirectResponse|Redirector
    {
        $remover->remove(
            $this->user,
            $this->company,
            $this->user
        );

        $this->confirmingLeavingCompany = false;

        $this->company = $this->company->fresh();

        return redirect(config('fortify.home'));
    }

    /**
     * Confirm that the given company employee should be removed.
     *
     * @param int $userId
     * @return void
     */
    public function confirmCompanyEmployeeRemoval(int $userId): void
    {
        $this->confirmingCompanyEmployeeRemoval = true;

        $this->companyEmployeeIdBeingRemoved = $userId;
    }

    /**
     * Remove a company employee from the company.
     *
     * @param RemovesCompanyEmployees $remover
     * @return void
     */
    public function removeCompanyEmployee(RemovesCompanyEmployees $remover): void
    {
        $remover->remove(
            $this->user,
            $this->company,
            $user = FilamentCompanies::findUserByIdOrFail($this->companyEmployeeIdBeingRemoved)
        );

        $this->confirmingCompanyEmployeeRemoval = false;

        $this->companyEmployeeIdBeingRemoved = null;

        $this->company = $this->company->fresh();
    }

    /**
     * Get the current user of the application.
     *
     * @return Authenticatable|User|null
     */
    public function getUserProperty(): Authenticatable|null|User
    {
        return Auth::user();
    }

    /**
     * Get the available company employee roles.
     *
     * @return array
     */
    public function getRolesProperty(): array
    {
        return collect(FilamentCompanies::$roles)->transform(function ($role) {
            return with($role->jsonSerialize(), function ($data) {
                return (new Role(
                    $data['key'],
                    $data['name'],
                    $data['permissions']
                ))->description($data['description']);
            });
        })->values()->all();
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('filament-companies::companies.company-employee-manager');
    }
}
