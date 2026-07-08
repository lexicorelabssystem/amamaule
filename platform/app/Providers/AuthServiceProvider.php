<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\Import;
use App\Models\Proposal;
use App\Policies\ActivityPolicy;
use App\Policies\ArtistPolicy;
use App\Policies\ImportPolicy;
use App\Policies\ProposalPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Activity::class => ActivityPolicy::class,
        Artist::class => ArtistPolicy::class,
        Import::class => ImportPolicy::class,
        Proposal::class => ProposalPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
