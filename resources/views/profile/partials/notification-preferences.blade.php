{{--
    Email notification checkboxes for the profile forms. Each checkbox follows a
    hidden "0" field: an unchecked box isn't submitted, so without it turning a
    setting off would never be saved.
--}}
<div class="block mt-4">
    <input type="hidden" name="receive_upload_notifications" value="0">
    <label for="receive_upload_notifications" class="inline-flex items-center">
        <input id="receive_upload_notifications" type="checkbox" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]" name="receive_upload_notifications" value="1" {{ old('receive_upload_notifications', $user->receive_upload_notifications) ? 'checked' : '' }}>
        <span class="ms-2 text-sm text-gray-600">{{ __('messages.profile_receive_notifications_label') }}</span>
    </label>
</div>

@if ($user->isAdmin() || $user->isEmployee())
    <div class="block mt-4">
        <input type="hidden" name="receive_share_download_notifications" value="0">
        <label for="receive_share_download_notifications" class="inline-flex items-center">
            <input id="receive_share_download_notifications" type="checkbox" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]" name="receive_share_download_notifications" value="1" {{ old('receive_share_download_notifications', $user->receive_share_download_notifications) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('messages.shares_profile_notifications_label') }}</span>
        </label>
    </div>
@endif
