<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Company management (admin only). Companies are never deleted through the web interface (R3).
 */
class CompanyController extends Controller
{
    public function index(): View
    {
        return view('companies.index', [
            'companies' => Company::query()->where('deactivated', false)->orderBy('company_name')->get(),
        ]);
    }

    public function deactivated(): View
    {
        return view('companies.deactivated', [
            'companies' => Company::query()->where('deactivated', true)->orderBy('company_name')->get(),
        ]);
    }

    public function show(Company $company): View
    {
        return view('companies.show', [
            'company' => $company,
            'products' => $company->products()->orderBy('name_en')->get(),
        ]);
    }

    public function create(): View
    {
        return view('companies.form', ['company' => new Company]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create($request->validated());

        return redirect('/companies/'.$company->id);
    }

    public function edit(Company $company): View
    {
        return view('companies.form', ['company' => $company]);
    }

    public function update(StoreCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect('/companies/'.$company->id);
    }

    public function deactivate(Company $company): RedirectResponse
    {
        $company->deactivate();

        return redirect('/companies/'.$company->id);
    }

    public function reactivate(Company $company): RedirectResponse
    {
        $company->reactivate();

        return redirect('/companies/'.$company->id);
    }
}
