# Design Document

## Overview

This design enhances the primary contact management system by improving the user interface, adding clear explanations of functionality, and providing better management capabilities. The design maintains the existing backend functionality while significantly improving the user experience around understanding and managing primary contact designations.

## Architecture

### Current System Analysis

The current primary contact system consists of:
- **Database**: `client_user_relationships` table with `is_primary` boolean field
- **Model**: `User::primaryCompanyUser()` method for retrieving primary contacts
- **Controller**: `AdminUserController::updateTeamAssignments()` for managing relationships
- **UI**: Basic radio button selection in admin user show view

### Enhanced System Components

1. **Enhanced UI Components**
   - Improved primary contact selection interface
   - Explanatory help text and tooltips
   - Visual prominence for current primary contact
   - Confirmation dialogs for changes

2. **Dashboard Enhancements**
   - Primary contact statistics on user dashboards
   - Client filtering by primary contact status
   - Clear visual indicators in client lists

3. **Validation Enhancements**
   - Prevent removal of last primary contact
   - Automatic primary contact assignment for new relationships
   - Better error handling and user feedback

## Components and Interfaces

### UI Components

#### 1. Enhanced Primary Contact Section
**Location**: `resources/views/admin/users/show.blade.php` (Team Access tab)

```blade
<!-- Primary Contact Explanation -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">About Primary Contact</h3>
            <div class="mt-2 text-sm text-blue-700">
                <p>The primary contact receives file uploads and notifications when clients don't select a specific recipient. Only one team member can be the primary contact.</p>
            </div>
        </div>
    </div>
</div>

<!-- Current Primary Contact Display -->
@if($client->primaryCompanyUser())
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Current Primary Contact</h3>
                <p class="text-sm text-green-700">
                    <strong>{{ $client->primaryCompanyUser()->name }}</strong> ({{ $client->primaryCompanyUser()->email }})
                </p>
            </div>
        </div>
    </div>
@endif
```

#### 2. Enhanced Team Member Selection
**Enhancement**: Add visual indicators and improved layout for team member selection

```blade
<div class="space-y-4">
    @foreach($availableTeamMembers as $member)
        <div class="border rounded-lg p-4 {{ $client->companyUsers->where('pivot.is_primary', true)->contains($member->id) ? 'border-green-300 bg-green-50' : 'border-gray-200' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="team_members[]" 
                           value="{{ $member->id }}"
                           id="member_{{ $member->id }}"
                           {{ $client->companyUsers->contains($member->id) ? 'checked' : '' }}
                           class="h-4 w-4 text-[var(--brand-color)] focus:ring-[var(--brand-color)] border-gray-300 rounded">
                    <label for="member_{{ $member->id }}" class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                        <div class="text-sm text-gray-500">{{ $member->email }} • {{ ucfirst($member->role->value) }}</div>
                    </label>
                </div>
                
                <div class="flex items-center space-x-4">
                    @if($client->companyUsers->where('pivot.is_primary', true)->contains($member->id))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Primary Contact
                        </span>
                    @endif
                    
                    <div class="flex items-center">
                        <input type="radio" 
                               name="primary_contact" 
                               value="{{ $member->id }}"
                               id="primary_{{ $member->id }}"
                               {{ $client->companyUsers->where('pivot.is_primary', true)->contains($member->id) ? 'checked' : '' }}
                               class="h-4 w-4 text-[var(--brand-color)] focus:ring-[var(--brand-color)] border-gray-300"
                               x-on:change="showPrimaryContactConfirmation = true; newPrimaryContact = '{{ $member->name }}'">
                        <label for="primary_{{ $member->id }}" class="ml-2 text-sm text-gray-600">
                            Make Primary
                        </label>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

#### 3. Confirmation Dialog Component
**New Component**: `resources/views/components/primary-contact-confirmation-modal.blade.php`

```blade
<!-- Primary Contact Change Confirmation Modal -->
<div x-show="showPrimaryContactConfirmation" 
     x-cloak
     class="fixed inset-0 z-[9999] overflow-y-auto"
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showPrimaryContactConfirmation"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 modal-backdrop transition-opacity z-[9998]"
             x-on:click="showPrimaryContactConfirmation = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div x-show="showPrimaryContactConfirmation"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10000] relative">
            
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Change Primary Contact
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to make <strong x-text="newPrimaryContact"></strong> the primary contact for this client? 
                                This person will receive all file uploads and notifications when no specific recipient is selected.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button x-on:click="confirmPrimaryContactChange()"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Yes, Change Primary Contact
                </button>
                <button x-on:click="showPrimaryContactConfirmation = false"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
```

#### 4. Dashboard Enhancement Component
**New Component**: `resources/views/components/dashboard/primary-contact-stats.blade.php`

```blade
@props(['user'])

<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Primary Contact For</dt>
                    <dd class="text-lg font-medium text-gray-900">
                        {{ $user->clientUsers()->wherePivot('is_primary', true)->count() }} 
                        {{ $user->clientUsers()->wherePivot('is_primary', true)->count() === 1 ? 'Client' : 'Clients' }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="bg-gray-50 px-5 py-3">
        <div class="text-sm">
            <a href="{{ route(auth()->user()->role->value === 'admin' ? 'admin.users.index' : 'employee.clients.index', ['username' => $user->username]) }}?filter=primary_contact" 
               class="font-medium text-blue-600 hover:text-blue-500">
                View clients
            </a>
        </div>
    </div>
</div>
```

### Backend Enhancements

#### 1. Enhanced Controller Validation
**File**: `app/Http/Controllers/Admin/AdminUserController.php`

```php
public function updateTeamAssignments(Request $request, $id)
{
    // ... existing code ...
    
    $validated = $request->validate([
        'team_members' => 'required|array|min:1',
        'team_members.*' => 'exists:users,id',
        'primary_contact' => 'required|exists:users,id|in:' . implode(',', $request->input('team_members', [])),
    ], [
        'team_members.required' => 'At least one team member must be assigned.',
        'team_members.min' => 'At least one team member must be assigned.',
        'primary_contact.required' => 'A primary contact must be selected.',
        'primary_contact.in' => 'The primary contact must be one of the selected team members.',
    ]);
    
    // ... rest of method ...
}
```

#### 2. Enhanced User Model Methods
**File**: `app/Models/User.php`

```php
/**
 * Get clients where this user is the primary contact.
 */
public function primaryContactClients()
{
    return $this->clientUsers()
        ->wherePivot('is_primary', true);
}

/**
 * Check if this user is the primary contact for a specific client.
 */
public function isPrimaryContactFor(User $client): bool
{
    return $this->clientUsers()
        ->wherePivot('is_primary', true)
        ->where('users.id', $client->id)
        ->exists();
}
```

## Data Models

### Existing Models (No Changes Required)

The current data model is sufficient:
- `client_user_relationships` table with `is_primary` boolean field
- `User` model with existing relationships
- Pivot table functionality working correctly

### Query Optimizations

Add database indexes for better performance:

```sql
-- Index for primary contact queries
ALTER TABLE client_user_relationships 
ADD INDEX idx_primary_contact (client_user_id, is_primary);

-- Index for company user queries  
ALTER TABLE client_user_relationships 
ADD INDEX idx_company_user_primary (company_user_id, is_primary);
```

## Error Handling

### Validation Errors

1. **No Team Members Selected**: Clear error message requiring at least one team member
2. **No Primary Contact Selected**: Require primary contact selection when team members are assigned
3. **Invalid Primary Contact**: Ensure primary contact is among selected team members
4. **Database Errors**: Graceful handling with user-friendly error messages

### Edge Cases

1. **Last Team Member Removal**: Prevent removal if it would leave client with no team members
2. **Primary Contact Removal**: Require new primary contact selection before removing current one
3. **Concurrent Updates**: Handle race conditions with proper database locking

## Testing Strategy

### Unit Tests

1. **Model Tests**:
   - `User::primaryCompanyUser()` method
   - `User::primaryContactClients()` method
   - `User::isPrimaryContactFor()` method

2. **Controller Tests**:
   - Team assignment validation
   - Primary contact requirement validation
   - Error handling scenarios

### Feature Tests

1. **UI Interaction Tests**:
   - Primary contact selection workflow
   - Confirmation dialog functionality
   - Dashboard statistics display

2. **Integration Tests**:
   - End-to-end team assignment workflow
   - Primary contact change impact on file uploads
   - Notification routing with primary contacts

### Browser Tests

1. **User Experience Tests**:
   - Modal interactions and confirmations
   - Visual feedback and error states
   - Responsive design on different screen sizes

2. **Accessibility Tests**:
   - Keyboard navigation
   - Screen reader compatibility
   - Color contrast and visual indicators