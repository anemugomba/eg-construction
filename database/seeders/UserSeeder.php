<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default password for all seeded users
        $password = Hash::make('password');

        // ===========================================
        // ADMINISTRATORS - Full access to everything
        // ===========================================
        $admin = User::firstOrCreate(
            ['email' => 'admin@egconstruction.co.zw'],
            [
                'name' => 'System Administrator',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_ADMINISTRATOR,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'anesucain@gmail.com'],
            [
                'name' => 'Anesu Cain Mugomba',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_ADMINISTRATOR,
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => true,
            ]
        );

        // ===========================================
        // TAX & INSURANCE MANAGEMENT USERS
        // These users focus on tax/insurance only
        // They have view_only access to fleet maintenance
        // ===========================================
        User::firstOrCreate(
            ['email' => 'tax.manager@egconstruction.co.zw'],
            [
                'name' => 'Tax Manager',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_VIEW_ONLY, // View only for fleet maintenance
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'tax.clerk@egconstruction.co.zw'],
            [
                'name' => 'Tax Clerk',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_VIEW_ONLY,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'insurance@egconstruction.co.zw'],
            [
                'name' => 'Insurance Officer',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_VIEW_ONLY,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => true,
            ]
        );

        // ===========================================
        // FLEET MAINTENANCE - SENIOR DPF
        // Can approve services, job cards, inspections
        // ===========================================
        $seniorDpf1 = User::firstOrCreate(
            ['email' => 'senior.dpf@egconstruction.co.zw'],
            [
                'name' => 'Senior DPF Manager',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SENIOR_DPF,
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => true,
            ]
        );

        $seniorDpf2 = User::firstOrCreate(
            ['email' => 'fleet.supervisor@egconstruction.co.zw'],
            [
                'name' => 'Fleet Supervisor',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SENIOR_DPF,
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => false,
            ]
        );

        // ===========================================
        // FLEET MAINTENANCE - SITE DPF
        // Can submit services, job cards, inspections
        // Assigned to specific sites
        // ===========================================
        $siteDpf1 = User::firstOrCreate(
            ['email' => 'dpf.mainyard@egconstruction.co.zw'],
            [
                'name' => 'DPF - Main Yard',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SITE_DPF,
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => false,
            ]
        );

        $siteDpf2 = User::firstOrCreate(
            ['email' => 'dpf.kariba@egconstruction.co.zw'],
            [
                'name' => 'DPF - Kariba Site',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SITE_DPF,
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => false,
            ]
        );

        $siteDpf3 = User::firstOrCreate(
            ['email' => 'dpf.hwange@egconstruction.co.zw'],
            [
                'name' => 'DPF - Hwange Site',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SITE_DPF,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => true,
            ]
        );

        $siteDpf4 = User::firstOrCreate(
            ['email' => 'dpf.bulawayo@egconstruction.co.zw'],
            [
                'name' => 'DPF - Bulawayo Site',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SITE_DPF,
                'notify_email' => true,
                'notify_sms' => true,
                'notify_whatsapp' => false,
            ]
        );

        // ===========================================
        // FLEET MAINTENANCE - DATA ENTRY
        // Can record readings, enter basic data
        // ===========================================
        User::firstOrCreate(
            ['email' => 'data.entry1@egconstruction.co.zw'],
            [
                'name' => 'Data Entry Clerk 1',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_DATA_ENTRY,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'data.entry2@egconstruction.co.zw'],
            [
                'name' => 'Data Entry Clerk 2',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_DATA_ENTRY,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        // ===========================================
        // VIEW ONLY - Reports & Monitoring
        // ===========================================
        User::firstOrCreate(
            ['email' => 'management@egconstruction.co.zw'],
            [
                'name' => 'Management User',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_VIEW_ONLY,
                'notify_email' => true,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'reports@egconstruction.co.zw'],
            [
                'name' => 'Reports Viewer',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_VIEW_ONLY,
                'notify_email' => false,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        // ===========================================
        // TEST ACCOUNTS (for development/testing)
        // ===========================================
        $testAdmin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Administrator',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_ADMINISTRATOR,
                'notify_email' => false,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        $testSenior = User::firstOrCreate(
            ['email' => 'senior@test.com'],
            [
                'name' => 'Test Senior DPF',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SENIOR_DPF,
                'notify_email' => false,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        $testSiteDpf = User::firstOrCreate(
            ['email' => 'sitedpf@test.com'],
            [
                'name' => 'Test Site DPF',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_SITE_DPF,
                'notify_email' => false,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        $testDataEntry = User::firstOrCreate(
            ['email' => 'dataentry@test.com'],
            [
                'name' => 'Test Data Entry',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_DATA_ENTRY,
                'notify_email' => false,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        $testViewer = User::firstOrCreate(
            ['email' => 'viewer@test.com'],
            [
                'name' => 'Test View Only',
                'password' => $password,
                'email_verified_at' => now(),
                'role' => User::ROLE_VIEW_ONLY,
                'notify_email' => false,
                'notify_sms' => false,
                'notify_whatsapp' => false,
            ]
        );

        // ===========================================
        // ASSIGN SITE DPF USERS TO THEIR SITES
        // ===========================================
        $this->assignUsersToSites($seniorDpf1, $seniorDpf2, $siteDpf1, $siteDpf2, $siteDpf3, $siteDpf4);

        // Assign test users to sites (all sites for broader testing)
        $this->assignTestUsersToSites($testSenior, $testSiteDpf, $testDataEntry, $testViewer);
    }

    /**
     * Assign users to their respective sites.
     */
    private function assignUsersToSites($seniorDpf1, $seniorDpf2, $siteDpf1, $siteDpf2, $siteDpf3, $siteDpf4): void
    {
        // Get sites by name
        $mainYard = Site::where('name', 'Main Yard')->first();
        $kariba = Site::where('name', 'Site A - Kariba')->first();
        $hwange = Site::where('name', 'Site B - Hwange')->first();
        $bulawayo = Site::where('name', 'Site C - Bulawayo')->first();
        $mutare = Site::where('name', 'Site D - Mutare')->first();
        $masvingo = Site::where('name', 'Site E - Masvingo')->first();

        // Senior DPFs have access to all sites
        $allSites = Site::pluck('id')->toArray();
        if ($seniorDpf1 && count($allSites) > 0) {
            $seniorDpf1->sites()->syncWithoutDetaching($allSites);
        }
        if ($seniorDpf2 && count($allSites) > 0) {
            $seniorDpf2->sites()->syncWithoutDetaching($allSites);
        }

        // Site DPFs assigned to their specific sites
        if ($siteDpf1 && $mainYard) {
            $siteDpf1->sites()->syncWithoutDetaching([$mainYard->id]);
        }
        if ($siteDpf2 && $kariba) {
            $siteDpf2->sites()->syncWithoutDetaching([$kariba->id]);
        }
        if ($siteDpf3 && $hwange) {
            $siteDpf3->sites()->syncWithoutDetaching([$hwange->id]);
        }
        if ($siteDpf4 && $bulawayo) {
            $siteDpf4->sites()->syncWithoutDetaching([$bulawayo->id]);
        }
    }

    /**
     * Assign test users to sites for development/testing.
     */
    private function assignTestUsersToSites($testSenior, $testSiteDpf, $testDataEntry, $testViewer): void
    {
        $allSites = Site::pluck('id')->toArray();

        if (count($allSites) === 0) {
            return;
        }

        // Test Senior DPF has access to all sites
        if ($testSenior) {
            $testSenior->sites()->syncWithoutDetaching($allSites);
        }

        // Test Site DPF has access to all sites (for easier testing)
        if ($testSiteDpf) {
            $testSiteDpf->sites()->syncWithoutDetaching($allSites);
        }

        // Test Data Entry has access to first 2 sites
        if ($testDataEntry && count($allSites) >= 2) {
            $testDataEntry->sites()->syncWithoutDetaching(array_slice($allSites, 0, 2));
        }

        // Test Viewer has access to first site only
        if ($testViewer && count($allSites) >= 1) {
            $testViewer->sites()->syncWithoutDetaching([$allSites[0]]);
        }
    }
}
