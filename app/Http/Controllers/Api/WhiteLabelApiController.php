<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhiteLabelClient;
use App\Services\WhiteLabelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WhiteLabelApiController extends Controller
{
    private WhiteLabelService $whiteLabelService;

    public function __construct(WhiteLabelService $whiteLabelService)
    {
        $this->whiteLabelService = $whiteLabelService;
        $this->middleware('auth:sanctum')->except(['getClientConfig', 'getClientPages', 'getClientMenu']);
    }

    public function getClients(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:active,inactive,suspended,trial',
            'type' => 'sometimes|in:subdomain,path,domain',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $query = WhiteLabelClient::query()
            ->with(['branding', 'usage' => function($q) {
                $q->recent(30);
            }]);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        $perPage = $request->get('per_page', 15);
        $clients = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'clients' => $clients
        ]);
    }

    public function createClient(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:white_label_clients,slug',
            'domain' => 'nullable|string|max:255|unique:white_label_clients,domain',
            'type' => ['required', Rule::in(['subdomain', 'path', 'domain'])],
            'contact_email' => 'required|email',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'monthly_fee' => 'required|numeric|min:0',
            'max_jobs' => 'required|integer|min:1',
            'max_companies' => 'required|integer|min:1',
            'max_users' => 'required|integer|min:1',
            'features_enabled' => 'sometimes|array',
            'custom_branding' => 'sometimes|boolean',
            'custom_domain' => 'sometimes|boolean',
            'api_access' => 'sometimes|boolean',
            'trial_days' => 'sometimes|integer|min:0|max:365'
        ]);

        try {
            $clientData = $request->only([
                'name', 'slug', 'domain', 'type', 'contact_email', 'contact_name',
                'contact_phone', 'description', 'monthly_fee', 'max_jobs',
                'max_companies', 'max_users', 'features_enabled', 'custom_branding',
                'custom_domain', 'api_access'
            ]);

            // Set trial end date if trial_days provided
            if ($request->filled('trial_days')) {
                $clientData['trial_ends_at'] = now()->addDays($request->trial_days);
                $clientData['status'] = 'trial';
            }

            $client = $this->whiteLabelService->createClient($clientData);

            return response()->json([
                'success' => true,
                'message' => 'White label client created successfully',
                'client' => $client
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create white label client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClient(WhiteLabelClient $client): JsonResponse
    {
        $client->load([
            'branding', 'settings', 'pages', 'menuItems',
            'emailTemplates', 'domains', 'theme', 'apiKeys'
        ]);

        return response()->json([
            'success' => true,
            'client' => $client,
            'usage_stats' => $client->getUsageStats(30)
        ]);
    }

    public function updateClient(Request $request, WhiteLabelClient $client): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:100|unique:white_label_clients,slug,' . $client->id,
            'domain' => 'nullable|string|max:255|unique:white_label_clients,domain,' . $client->id,
            'contact_email' => 'sometimes|email',
            'contact_name' => 'sometimes|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:active,inactive,suspended,trial',
            'monthly_fee' => 'sometimes|numeric|min:0',
            'max_jobs' => 'sometimes|integer|min:1',
            'max_companies' => 'sometimes|integer|min:1',
            'max_users' => 'sometimes|integer|min:1',
            'features_enabled' => 'sometimes|array',
            'custom_branding' => 'sometimes|boolean',
            'custom_domain' => 'sometimes|boolean',
            'api_access' => 'sometimes|boolean'
        ]);

        try {
            $client->update($request->only([
                'name', 'slug', 'domain', 'contact_email', 'contact_name',
                'contact_phone', 'description', 'status', 'monthly_fee',
                'max_jobs', 'max_companies', 'max_users', 'features_enabled',
                'custom_branding', 'custom_domain', 'api_access'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully',
                'client' => $client->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateBranding(Request $request, WhiteLabelClient $client): JsonResponse
    {
        $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'site_tagline' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|string|max:1000',
            'logo_url' => 'sometimes|url',
            'favicon_url' => 'sometimes|url',
            'color_scheme' => 'sometimes|array',
            'typography' => 'sometimes|array',
            'custom_css' => 'sometimes|array',
            'footer_content' => 'sometimes|array',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:255',
            'social_links' => 'sometimes|array',
            'google_analytics_id' => 'sometimes|string|max:50',
            'facebook_pixel_id' => 'sometimes|string|max:50',
            'custom_head_code' => 'sometimes|string',
            'custom_body_code' => 'sometimes|string'
        ]);

        try {
            $branding = $this->whiteLabelService->updateBranding($client, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Branding updated successfully',
                'branding' => $branding
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branding',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateApiKey(Request $request, WhiteLabelClient $client): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'sometimes|array',
            'rate_limits' => 'sometimes|array',
            'expires_at' => 'sometimes|date|after:now'
        ]);

        try {
            $apiKey = $this->whiteLabelService->generateApiKey(
                $client,
                $request->name,
                $request->get('permissions', []),
                $request->get('rate_limits', [])
            );

            if ($request->filled('expires_at')) {
                $apiKey->update(['expires_at' => $request->expires_at]);
            }

            return response()->json([
                'success' => true,
                'message' => 'API key generated successfully',
                'api_key' => $apiKey
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyDomain(Request $request, WhiteLabelClient $client): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|max:255'
        ]);

        try {
            $domain = $this->whiteLabelService->verifyDomain($client, $request->domain);

            return response()->json([
                'success' => true,
                'message' => 'Domain verification initiated',
                'domain' => $domain,
                'dns_records' => $domain->getRequiredDnsRecords()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify domain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClientConfig(Request $request): JsonResponse
    {
        $client = $this->whiteLabelService->getClientByRequest($request);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'White label client not found'
            ], 404);
        }

        $config = $this->whiteLabelService->getClientConfiguration($client);

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    public function getClientPages(Request $request): JsonResponse
    {
        $client = $this->whiteLabelService->getClientByRequest($request);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'White label client not found'
            ], 404);
        }

        $pages = $client->pages()->published()->ordered()->get();

        return response()->json([
            'success' => true,
            'pages' => $pages
        ]);
    }

    public function getClientMenu(Request $request): JsonResponse
    {
        $request->validate([
            'location' => 'sometimes|in:header,footer,sidebar'
        ]);

        $client = $this->whiteLabelService->getClientByRequest($request);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'White label client not found'
            ], 404);
        }

        $query = $client->menuItems()->active()->topLevel()->with('children');

        if ($request->filled('location')) {
            $query->byLocation($request->location);
        }

        $menuItems = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'menu_items' => $menuItems
        ]);
    }

    public function getUsageStats(Request $request, WhiteLabelClient $client): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365'
        ]);

        $days = $request->get('days', 30);
        $stats = $client->getUsageStats($days);

        return response()->json([
            'success' => true,
            'usage_stats' => $stats
        ]);
    }

    public function extendTrial(Request $request, WhiteLabelClient $client): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        if (!$client->is_trial) {
            return response()->json([
                'success' => false,
                'message' => 'Client is not on trial'
            ], 400);
        }

        try {
            $client->extendTrial($request->days);

            return response()->json([
                'success' => true,
                'message' => 'Trial extended successfully',
                'trial_ends_at' => $client->fresh()->trial_ends_at
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend trial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activate(WhiteLabelClient $client): JsonResponse
    {
        if ($client->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Client is already active'
            ], 400);
        }

        try {
            $client->activate();

            return response()->json([
                'success' => true,
                'message' => 'Client activated successfully',
                'client' => $client->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function suspend(WhiteLabelClient $client): JsonResponse
    {
        if ($client->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Client is already suspended'
            ], 400);
        }

        try {
            $client->suspend();

            return response()->json([
                'success' => true,
                'message' => 'Client suspended successfully',
                'client' => $client->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableFeatures(): JsonResponse
    {
        $features = $this->whiteLabelService->getAvailableFeatures();

        return response()->json([
            'success' => true,
            'features' => $features
        ]);
    }

    public function exportClientData(WhiteLabelClient $client): JsonResponse
    {
        try {
            $data = $this->whiteLabelService->exportClientData($client);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export client data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteClient(WhiteLabelClient $client): JsonResponse
    {
        try {
            $clientName = $client->name;
            $client->delete();

            return response()->json([
                'success' => true,
                'message' => "Client '{$clientName}' deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}