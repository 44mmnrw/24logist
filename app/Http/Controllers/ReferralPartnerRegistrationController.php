<?php

namespace App\Http\Controllers;

use App\Models\ReferralPartnerApplication;
use App\Models\ReferralProgramSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReferralPartnerRegistrationController extends Controller
{
    public function create(): View
    {
        return view('referrals.register', ['settings' => ReferralProgramSetting::current()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'inn' => preg_replace('/\D+/', '', (string) $request->input('inn')),
            'contact_phone' => trim((string) $request->input('contact_phone')),
        ]);
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^\d{10}(\d{2})?$/', 'unique:referral_partner_applications,inn'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{10,25}$/'],
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
            'website' => ['nullable', 'prohibited'],
        ], [
            'inn.unique' => 'Заявка от компании с таким ИНН уже зарегистрирована.',
            'inn.regex' => 'ИНН должен содержать 10 или 12 цифр.',
        ]);

        try {
            ReferralPartnerApplication::query()->create([
                'company_name' => trim($data['company_name']),
                'inn' => $data['inn'],
                'contact_name' => trim($data['contact_name']),
                'contact_email' => mb_strtolower(trim($data['contact_email'])),
                'contact_phone' => $data['contact_phone'],
                'status' => 'pending',
                'terms_accepted' => true,
                'privacy_accepted' => true,
                'ip_hash' => filled($request->ip()) ? hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')) : null,
                'user_agent_hash' => filled($request->userAgent()) ? hash_hmac('sha256', (string) $request->userAgent(), (string) config('app.key')) : null,
            ]);
        } catch (QueryException) {
            return back()->withInput()->withErrors(['inn' => 'Заявка от компании с таким ИНН уже зарегистрирована.']);
        }

        return redirect()->route('referrals.partners.registered');
    }

    public function registered(): View
    {
        return view('referrals.registered');
    }
}
