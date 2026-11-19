# Graph Report - cms-laravel-template-badilag  (2026-11-19)

## Corpus Check
- 172 files · ~59,619 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 779 nodes · 1399 edges · 102 communities (93 shown, 9 thin omitted)
- Extraction: 92% EXTRACTED · 8% INFERRED · 0% AMBIGUOUS · INFERRED: 113 edges (avg confidence: 0.82)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `89049b86`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Public Controllers & Kepaniteraan
- Criteria Importer & Compliance Checks
- Database Model Factories
- Fortify Actions & Local Captcha
- PRD, Design & Architecture Docs
- Admin Livewire Modules & Posts
- Public Portal Route Handlers
- Security & Redirect Middlewares
- Composer Scripts & Tooling
- Node Vite & Tailwind Setup
- PostManager Livewire Actions
- MenuManager Livewire Actions
- DocumentManager Livewire Actions
- SliderManager Livewire Actions
- Database Seeders & Config
- MenuManager Blade UI
- PageManager Livewire Actions
- Composer Dev Dependencies
- CriteriaManager Blade UI
- Composer Runtime Dependencies
- SinovaLinks Blade UI
- Composer Package Metadata
- Composer Plugin Configuration
- PageManager Blade UI
- Dynamic Page Block Includes
- Composer PSR-4 App Autoload
- SliderManager Blade UI
- KepaniteraanManager Blade UI
- Pest & PHPUnit Test Setup
- DocumentManager Blade UI
- PostManager Blade UI
- UserRoleManager Blade UI
- AuditAndBackup Blade UI
- SafeHtml XSS Sanitizer
- SatkerSettingsModal Blade UI
- Composer Dev Test Autoload
- Composer Laravel Discovery
- Composer Framework Keywords
- Boost MCP Configuration
- Spatie Backup Config
- SetupWizard Blade UI
- TwoFactorSetup Blade UI
- Admin Dashboard Blade UI

## God Nodes (most connected - your core abstractions)
1. `SatkerSetting` - 44 edges
2. `CriteriaItem` - 36 edges
3. `Page` - 36 edges
4. `User` - 29 edges
5. `MenuItem` - 28 edges
6. `SinovaHealthCheckService` - 22 edges
7. `CriteriaGroup` - 19 edges
8. `Post` - 18 edges
9. `CriteriaManager` - 17 edges
10. `MenuManager` - 16 edges

## Surprising Connections (you probably didn't know these)
- `Visual Dials (Energy 1 / Rhythm 2 / Motion 1)` --semantically_similar_to--> `Homepage Performance Guardrails (< 5s)`  [INFERRED] [semantically similar]
  DESIGN.md → docs/PRD-CMS-Pengadilan-Agama.md
- `seedAdminTestEnvironment()` --calls--> `SatkerSetting`  [INFERRED]
  tests/Feature/AdminPanelTest.php → app/Models/SatkerSetting.php
- `Six-Role RBAC (spatie/laravel-permission)` --shares_data_with--> `SatkerCMS Project Guidelines`  [INFERRED]
  docs/PRD-CMS-Pengadilan-Agama.md → .ai/guidelines/satker-cms.md
- `Figma Checkpoint Rule` --conceptually_related_to--> `Figma Screen Architecture (Pages 01-03)`  [INFERRED]
  .ai/guidelines/satker-cms.md → DESIGN.md
- `seedPublicPortal()` --references--> `SatkerSetting`  [EXTRACTED]
  tests/Feature/PublicPortalTest.php → app/Models/SatkerSetting.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **SINOVA Automated Compliance Pipeline** — docs_prd_cms_pengadilan_agama_skbadilag2722, docs_prd_cms_pengadilan_agama_sinovamodule, docs_prd_cms_pengadilan_agama_sinovalinks, docs_prd_cms_pengadilan_agama_linkhealthcheck, docs_prd_cms_pengadilan_agama_collections [EXTRACTED 1.00]
- **Badilag Brand & WCAG AA Accessibility Lock** — design_primaryemerald, design_secondarygold, design_highcontrasttokens, docs_prd_cms_pengadilan_agama_accessibilitywidget, _ai_guidelines_satker_cms_satkercmsguidelines [INFERRED 0.95]
- **Fase 0 Security & Setup Foundation** — docs_plan_fase_0_fase0plan, docs_plan_fase_0_fortifymfa, docs_plan_fase_0_setupwizard, docs_prd_cms_pengadilan_agama_rbac6roles, _ai_guidelines_satker_cms_securityheadersmiddleware [INFERRED 0.95]

## Communities (102 total, 9 thin omitted)

### Community 0 - "Public Controllers & Kepaniteraan"
Cohesion: 0.07
Nodes (26): TwoFactorSetup, LinkCheckLog, Official, LogOptions, Page, LogOptions, PageVisit, LogOptions (+18 more)

### Community 1 - "Criteria Importer & Compliance Checks"
Cohesion: 0.08
Nodes (7): CriteriaManager, SinovaLinks, CriteriaGroup, CriteriaItem, SinovaCriteriaImporter, CriteriaItemFactory, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 2 - "Database Model Factories"
Cohesion: 0.06
Nodes (16): CaseStatisticFactory, CourtScheduleFactory, CriteriaGroupFactory, DocumentFactory, HomeSliderFactory, LinkCheckLogFactory, OfficialFactory, PageFactory (+8 more)

### Community 3 - "Fortify Actions & Local Captcha"
Cohesion: 0.09
Nodes (12): CreateNewUser, ResetUserPassword, UpdateUserPassword, UpdateUserProfileInformation, AppServiceProvider, FortifyServiceProvider, Illuminate\Support\ServiceProvider, Laravel\Fortify\Contracts\CreatesNewUsers (+4 more)

### Community 4 - "PRD, Design & Architecture Docs"
Cohesion: 0.05
Nodes (45): App Actions & Validation Pattern, Figma Checkpoint Rule, Flux UI Free Tier Constraint, SatkerCMS Project Guidelines, SecurityHeadersMiddleware, Asymmetric Homepage Grid (8/4 Hero, 7/5 News), Embed Eksternal + Cadangan Manual UI Pattern, Figma Screen Architecture (Pages 01-03) (+37 more)

### Community 6 - "Public Portal Route Handlers"
Cohesion: 0.05
Nodes (12): CmsUpdateCommand, PublicComplianceController, Dashboard, SatkerSettingsManager, SetupWizard, LogOptions, SatkerSetting, SinovaHealthCheckService (+4 more)

### Community 7 - "Security & Redirect Middlewares"
Cohesion: 0.14
Nodes (10): EnsureSetupCompletedMiddleware, EnsureTwoFactorConfirmedMiddleware, HandleRedirectsMiddleware, SecurityHeadersMiddleware, TrackPageVisitMiddleware, AuditAndBackupManager, Redirect, Closure (+2 more)

### Community 8 - "Composer Scripts & Tooling"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 9 - "Node Vite & Tailwind Setup"
Cohesion: 0.10
Nodes (20): concurrently, @laravel/multiplex, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite (+12 more)

### Community 10 - "PostManager Livewire Actions"
Cohesion: 0.15
Nodes (5): PublicPostController, PostManager, LogOptions, Post, Illuminate\Http\Response

### Community 11 - "MenuManager Livewire Actions"
Cohesion: 0.15
Nodes (4): MenuManager, MenuItem, MenuItemFactory, Illuminate\Database\Eloquent\Relations\HasMany

### Community 12 - "DocumentManager Livewire Actions"
Cohesion: 0.16
Nodes (4): PublicSearchController, DocumentManager, Document, LogOptions

### Community 13 - "SliderManager Livewire Actions"
Cohesion: 0.10
Nodes (6): PublicHomeController, PageManager, SliderManager, HomeSlider, SafeHtml, self

### Community 14 - "Database Seeders & Config"
Cohesion: 0.28
Nodes (5): DatabaseSeeder, RoleAndPermissionSeeder, SinovaCriteriaSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 15 - "MenuManager Blade UI"
Cohesion: 0.11
Nodes (17): delete({{ $child->id }}), delete({{ $grandchild->id }}), delete({{ $menu->id }}), edit({{ $child->id }}), edit({{ $grandchild->id }}), edit({{ $menu->id }}), moveDown({{ $child->id }}), moveDown({{ $grandchild->id }}) (+9 more)

### Community 16 - "PageManager Livewire Actions"
Cohesion: 0.18
Nodes (5): Controller, PublicPageController, KepaniteraanManager, CaseStatistic, CourtSchedule

### Community 17 - "Composer Dev Dependencies"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision (+3 more)

### Community 18 - "CriteriaManager Blade UI"
Cohesion: 0.20
Nodes (9): applyImport, deleteGroup({{ $grp->id }}), deleteItem({{ $cItem->id }}), editGroup({{ $grp->id }}), editItem({{ $cItem->id }}), exportJson, newItem, previewImport (+1 more)

### Community 19 - "Composer Runtime Dependencies"
Cohesion: 0.20
Nodes (10): require, laravel/fortify, laravel/framework, laravel/tinker, livewire/flux, livewire/livewire, php, spatie/laravel-activitylog (+2 more)

### Community 20 - "SinovaLinks Blade UI"
Cohesion: 0.25
Nodes (7): cancelEdit, closeAll, exportCsv, openAll, runHealthCheck, startEdit({{ $item->id }}), toggleGroup({{ $group->id }})

### Community 21 - "Composer Package Metadata"
Cohesion: 0.25
Nodes (7): description, license, minimum-stability, name, prefer-stable, $schema, type

### Community 22 - "Composer Plugin Configuration"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 23 - "PageManager Blade UI"
Cohesion: 0.33
Nodes (5): addBlock(, delete({{ $pg->id }}), edit({{ $pg->id }}), removeBlock({{ $index }}), createNew

### Community 24 - "Dynamic Page Block Includes"
Cohesion: 0.33
Nodes (5): public.blocks.document-list, public.blocks.embed-fallback, public.blocks.external-portal, public.blocks.map-yurisdiksi, public.blocks.satker-contact

### Community 25 - "Composer PSR-4 App Autoload"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 26 - "SliderManager Blade UI"
Cohesion: 0.40
Nodes (4): delete({{ $slide->id }}), edit({{ $slide->id }}), createNew, toggleActive({{ $slide->id }})

### Community 27 - "KepaniteraanManager Blade UI"
Cohesion: 0.40
Nodes (4): deleteOfficial({{ $off->id }}), deleteSchedule({{ $sch->id }}), deleteStatistic({{ $st->id }}), $set(

### Community 29 - "DocumentManager Blade UI"
Cohesion: 0.50
Nodes (3): delete({{ $doc->id }}), edit({{ $doc->id }}), createNew

### Community 30 - "PostManager Blade UI"
Cohesion: 0.50
Nodes (3): delete({{ $post->id }}), edit({{ $post->id }}), createNew

### Community 31 - "UserRoleManager Blade UI"
Cohesion: 0.50
Nodes (3): delete({{ $usr->id }}), edit({{ $usr->id }}), createNew

### Community 32 - "AuditAndBackup Blade UI"
Cohesion: 0.50
Nodes (3): deleteRedirect({{ $rd->id }}), $set(, runLinkAudit

### Community 35 - "Composer Dev Test Autoload"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 36 - "Composer Laravel Discovery"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 37 - "Composer Framework Keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

## Knowledge Gaps
- **145 isolated node(s):** `php`, `$schema`, `name`, `type`, `description` (+140 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **9 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Public Controllers & Kepaniteraan` to `SafeHtml XSS Sanitizer`, `Fortify Actions & Local Captcha`, `Public Portal Route Handlers`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Why does `SatkerSetting` connect `Public Portal Route Handlers` to `Public Controllers & Kepaniteraan`, `Criteria Importer & Compliance Checks`, `Security & Redirect Middlewares`, `PostManager Livewire Actions`, `DocumentManager Livewire Actions`, `SliderManager Livewire Actions`, `PageManager Livewire Actions`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Why does `CriteriaItem` connect `Criteria Importer & Compliance Checks` to `Public Controllers & Kepaniteraan`, `DocumentManager Livewire Actions`, `SliderManager Livewire Actions`, `Public Portal Route Handlers`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Are the 16 inferred relationships involving `SatkerSetting` (e.g. with `.accessibility()` and `.sitemapHtml()`) actually correct?**
  _`SatkerSetting` has 16 INFERRED edges - model-reasoned connections that need verification._
- **What connects `php`, `$schema`, `name` to the rest of the system?**
  _145 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Public Controllers & Kepaniteraan` be split into smaller, more focused modules?**
  _Cohesion score 0.07290886392009988 - nodes in this community are weakly interconnected._
- **Should `Criteria Importer & Compliance Checks` be split into smaller, more focused modules?**
  _Cohesion score 0.07510204081632653 - nodes in this community are weakly interconnected._