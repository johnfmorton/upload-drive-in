<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSearchFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected array $testClients;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);
        
        // Create employee user
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'name' => 'Employee User',
            'owner_id' => $this->admin->id,
        ]);
        
        // Create test client users with various names and emails for search testing
        $this->testClients = [
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'Bob Johnson',
                'email' => 'bob@johnson.org',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'Alice Brown',
                'email' => 'alice.brown@test.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'Charlie Wilson',
                'email' => 'charlie@wilson.net',
            ]),
        ];
        
        // Set up primary contact relationships for some clients
        $this->testClients[0]->companyUsers()->attach($this->admin->id, ['is_primary' => true]);
        $this->testClients[1]->companyUsers()->attach($this->employee->id, ['is_primary' => true]);
        $this->testClients[2]->companyUsers()->attach($this->admin->id, ['is_primary' => true]);
    }

    // Test search functionality across all database records

    public function test_search_finds_users_by_name_across_all_pages()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('Bob Johnson'); // Contains "John" in last name
        $response->assertDontSee('Jane Smith');
        $response->assertDontSee('Alice Brown');
        $response->assertDontSee('Charlie Wilson');
    }

    public function test_search_finds_users_by_email_across_all_pages()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=company.com');
        
        $response->assertStatus(200);
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');
        $response->assertDontSee('Bob Johnson');
        $response->assertDontSee('Alice Brown');
        $response->assertDontSee('Charlie Wilson');
    }

    public function test_search_is_case_insensitive()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=JOHN');
        
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('Bob Johnson');
    }

    public function test_search_handles_partial_matches()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=Bro');
        
        $response->assertStatus(200);
        $response->assertSee('Alice Brown');
        $response->assertDontSee('John Doe');
    }

    public function test_search_matches_both_name_and_email_fields()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=test');
        
        $response->assertStatus(200);
        $response->assertSee('Alice Brown'); // email contains "test.com"
        $response->assertDontSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    public function test_empty_search_returns_all_users()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=');
        
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
        $response->assertSee('Bob Johnson');
        $response->assertSee('Alice Brown');
        $response->assertSee('Charlie Wilson');
    }

    public function test_search_with_no_results_shows_appropriate_message()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=nonexistent');
        
        $response->assertStatus(200);
        $response->assertSee('No client users match your search for "nonexistent"');
        $response->assertDontSee('John Doe');
    }

    // Test combination of search with primary contact filter

    public function test_search_works_with_primary_contact_filter()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John&filter=primary_contact');
        
        $response->assertStatus(200);
        $response->assertSee('John Doe'); // Primary contact for admin
        $response->assertSee('Bob Johnson'); // Primary contact for admin
        $response->assertDontSee('Jane Smith'); // Primary contact for employee, not admin
    }

    public function test_primary_contact_filter_with_search_maintains_both_conditions()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=Jane&filter=primary_contact');
        
        $response->assertStatus(200);
        $response->assertDontSee('Jane Smith'); // Jane is not primary contact for admin
        $response->assertSee('No client users match your search for "Jane"');
    }

    public function test_search_term_preserved_when_toggling_primary_contact_filter()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John&filter=primary_contact');
        
        $response->assertStatus(200);
        // Check that search term is preserved in form
        $response->assertSee('value="John"', false);
    }

    // Test pagination behavior with search parameters

    public function test_pagination_preserves_search_parameters()
    {
        $this->actingAs($this->admin);
        
        // Create more users to trigger pagination
        User::factory()->count(15)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Test User',
        ]);
        
        $response = $this->get('/admin/users?search=Test&page=2');
        
        $response->assertStatus(200);
        // Check that pagination links contain search parameter
        $response->assertSee('search=Test', false);
    }

    public function test_pagination_preserves_both_search_and_filter_parameters()
    {
        $this->actingAs($this->admin);
        
        // Create more primary contact users to trigger pagination
        $additionalClients = User::factory()->count(15)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Primary Test User',
        ]);
        
        foreach ($additionalClients as $client) {
            $client->companyUsers()->attach($this->admin->id, ['is_primary' => true]);
        }
        
        $response = $this->get('/admin/users?search=Primary&filter=primary_contact&page=2');
        
        $response->assertStatus(200);
        // Check that pagination links contain both parameters
        $response->assertSee('search=Primary', false);
        $response->assertSee('filter=primary_contact', false);
    }

    public function test_search_results_span_multiple_pages_correctly()
    {
        $this->actingAs($this->admin);
        
        // Create many users with "Search" in name to test pagination
        User::factory()->count(25)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Search Test User',
        ]);
        
        $response = $this->get('/admin/users?search=Search');
        
        $response->assertStatus(200);
        $response->assertSee('Search Test User');
        
        // Check that pagination is present for search results
        $response->assertSee('Next', false);
    }

    // Test empty search results and error handling

    public function test_search_handles_special_characters()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=' . urlencode('@#$%'));
        
        $response->assertStatus(200);
        $response->assertSee('No client users match your search');
    }

    public function test_search_handles_sql_injection_attempts()
    {
        $this->actingAs($this->admin);
        
        $maliciousSearch = "'; DROP TABLE users; --";
        $response = $this->get('/admin/users?search=' . urlencode($maliciousSearch));
        
        $response->assertStatus(200);
        // Verify users table still exists by checking we can see users
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    public function test_search_parameter_length_validation()
    {
        $this->actingAs($this->admin);
        
        $longSearch = str_repeat('a', 256); // Exceeds 255 character limit
        $response = $this->get('/admin/users?search=' . $longSearch);
        
        $response->assertSessionHasErrors(['search']);
    }

    // Test mobile and desktop view consistency

    public function test_search_results_consistent_across_responsive_views()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        
        // Both mobile and desktop views should show the same data
        // Mobile cards and desktop table should iterate over same $clients collection
        $response->assertSee('John Doe');
        $response->assertSee('Bob Johnson');
        
        // Check that both views are present in the template
        $response->assertSee('lg:hidden', false); // Mobile view
        $response->assertSee('hidden lg:block', false); // Desktop view
    }

    // Test search form functionality

    public function test_search_form_preserves_current_search_term()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        $response->assertSee('value="John"', false);
    }

    public function test_search_form_preserves_filter_state()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John&filter=primary_contact');
        
        $response->assertStatus(200);
        $response->assertSee('value="John"', false);
        $response->assertSee('name="filter" value="primary_contact"', false);
    }

    public function test_search_form_submission_method_is_get()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users');
        
        $response->assertStatus(200);
        $response->assertSee('method="GET"', false);
    }

    // Test URL structure and bookmarking

    public function test_search_results_have_bookmarkable_urls()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John&filter=primary_contact');
        
        $response->assertStatus(200);
        
        // Test that the same URL returns the same results
        $secondResponse = $this->get('/admin/users?search=John&filter=primary_contact');
        $secondResponse->assertStatus(200);
        
        // Both responses should show the same users (not comparing full content due to dynamic elements)
        $response->assertSee('John Doe');
        $response->assertSee('Bob Johnson');
        $secondResponse->assertSee('John Doe');
        $secondResponse->assertSee('Bob Johnson');
        
        // Both should preserve the search parameters
        $response->assertSee('value="John"', false);
        $response->assertSee('name="filter" value="primary_contact"', false);
        $secondResponse->assertSee('value="John"', false);
        $secondResponse->assertSee('name="filter" value="primary_contact"', false);
    }

    public function test_clearing_search_returns_to_normal_paginated_view()
    {
        $this->actingAs($this->admin);
        
        // First, perform a search
        $searchResponse = $this->get('/admin/users?search=John');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('John Doe');
        $searchResponse->assertDontSee('Alice Brown');
        
        // Then clear the search
        $clearResponse = $this->get('/admin/users');
        $clearResponse->assertStatus(200);
        $clearResponse->assertSee('John Doe');
        $clearResponse->assertSee('Alice Brown'); // Should see all users again
    }

    // Test performance and loading states

    public function test_search_completes_within_reasonable_time()
    {
        $this->actingAs($this->admin);
        
        // Create a larger dataset
        User::factory()->count(100)->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $startTime = microtime(true);
        $response = $this->get('/admin/users?search=test');
        $endTime = microtime(true);
        
        $response->assertStatus(200);
        
        // Should complete within 2 seconds as per requirements
        $this->assertLessThan(2.0, $endTime - $startTime);
    }

    // Test edge cases

    public function test_search_with_only_whitespace_treated_as_empty()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=' . urlencode('   '));
        
        $response->assertStatus(200);
        // Should show all users, not "no results" message
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
        $response->assertDontSee('No client users match your search');
    }

    public function test_search_handles_unicode_characters()
    {
        $this->actingAs($this->admin);
        
        // Create user with unicode characters
        $unicodeUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'name' => 'José García',
            'email' => 'jose@example.com',
        ]);
        
        $response = $this->get('/admin/users?search=' . urlencode('José'));
        
        $response->assertStatus(200);
        $response->assertSee('José García');
    }

    public function test_multiple_word_search_matches_exact_phrase()
    {
        $this->actingAs($this->admin);
        
        // Current implementation searches for the exact phrase, not individual words
        $response = $this->get('/admin/users?search=' . urlencode('John Smith'));
        
        $response->assertStatus(200);
        // Should show no results since no user has "John Smith" as exact phrase in name or email
        $response->assertSee('No client users match your search for "John Smith"');
        $response->assertDontSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    // Test authentication and authorization

    public function test_search_requires_authentication()
    {
        $response = $this->get('/admin/users?search=John');
        
        $response->assertRedirect('/login');
    }

    public function test_search_requires_admin_or_employee_role()
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $this->actingAs($client);
        
        $response = $this->get('/admin/users?search=John');
        
        // Should be forbidden or redirected based on middleware
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    // Test integration with existing functionality

    public function test_search_preserves_existing_modal_functionality()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        // Check that Alpine.js modal functionality is still present
        $response->assertSee('x-data', false);
        $response->assertSee('showDeleteModal', false);
    }

    public function test_search_preserves_copy_url_functionality()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        // Check that copy URL functionality is still present
        $response->assertSee('copyLoginUrl', false);
    }

    public function test_search_works_with_column_visibility_controls()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        // Check that column visibility controls are still present
        $response->assertSee('columns', false);
    }
}