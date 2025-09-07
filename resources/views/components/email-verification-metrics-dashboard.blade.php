<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Email Verification Metrics</h3>
        <div class="flex items-center space-x-2">
            @php $summary = $getSummaryStatus() @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $summary['color_class'] }}">
                {{ $summary['message'] }}
            </span>
            <span class="text-sm text-gray-500">Last {{ $hours }} hours</span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">🔓</span>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-900">Existing User Bypasses</p>
                    <p class="text-2xl font-bold text-blue-600">
                        {{ $formatNumber($metrics['last_24_hours']['existing_user_bypasses']) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">🚫</span>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-900">Restrictions Enforced</p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ $formatNumber($metrics['last_24_hours']['restriction_enforcements']) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">📊</span>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Bypass Ratio</p>
                    <p class="text-2xl font-bold text-gray-600">
                        {{ $getBypassRatio() }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    @if(!empty($alerts))
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-900 mb-3">🚨 Unusual Activity Alerts</h4>
            <div class="space-y-2">
                @foreach($alerts as $alert)
                    <div class="flex items-start p-3 rounded-lg border {{ $getAlertColorClass($alert['severity']) }}">
                        <span class="flex-shrink-0 mr-2">{{ $getAlertIcon($alert['severity']) }}</span>
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ $alert['type'] }}</p>
                            <p class="text-sm">{{ $alert['message'] }}</p>
                            @if(isset($alert['count']))
                                <p class="text-xs mt-1">Count: {{ $alert['count'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Detailed Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bypass Patterns -->
        <div>
            <h4 class="text-md font-medium text-gray-900 mb-3">🔓 Bypass Patterns</h4>
            
            @if(!empty($bypassPatterns['bypasses_by_role']))
                <div class="mb-4">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">By User Role</h5>
                    <div class="space-y-1">
                        @foreach($getTopItems($bypassPatterns['bypasses_by_role']) as $role => $count)
                            <div class="flex justify-between items-center py-1">
                                <span class="text-sm text-gray-600 capitalize">{{ $role }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($bypassPatterns['bypasses_by_restriction_type']))
                <div class="mb-4">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">By Restriction Type</h5>
                    <div class="space-y-1">
                        @foreach($getTopItems($bypassPatterns['bypasses_by_restriction_type']) as $type => $count)
                            <div class="flex justify-between items-center py-1">
                                <span class="text-sm text-gray-600">{{ str_replace('_', ' ', $type) }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($bypassPatterns['bypasses_by_domain']))
                <div>
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Top Bypass Domains</h5>
                    <div class="space-y-1">
                        @foreach($getTopItems($bypassPatterns['bypasses_by_domain']) as $domain => $count)
                            <div class="flex justify-between items-center py-1">
                                <span class="text-sm text-gray-600">{{ $domain }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Restriction Patterns -->
        <div>
            <h4 class="text-md font-medium text-gray-900 mb-3">🚫 Restriction Enforcement</h4>
            
            @if(!empty($restrictionPatterns['restrictions_by_type']))
                <div class="mb-4">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">By Restriction Type</h5>
                    <div class="space-y-1">
                        @foreach($getTopItems($restrictionPatterns['restrictions_by_type']) as $type => $count)
                            <div class="flex justify-between items-center py-1">
                                <span class="text-sm text-gray-600">{{ str_replace('_', ' ', $type) }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($restrictionPatterns['blocked_domains']))
                <div>
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Top Blocked Domains</h5>
                    <div class="space-y-1">
                        @foreach($getTopItems($restrictionPatterns['blocked_domains']) as $domain => $count)
                            <div class="flex justify-between items-center py-1">
                                <span class="text-sm text-gray-600">{{ $domain }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Activity Timeline -->
    @if(!empty($bypassPatterns['hourly_distribution']) || !empty($restrictionPatterns['hourly_distribution']))
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h4 class="text-md font-medium text-gray-900 mb-3">📈 Activity Timeline (Last {{ $hours }} hours)</h4>
            <div class="grid grid-cols-12 gap-1">
                @php
                    $maxActivity = max(
                        array_values($bypassPatterns['hourly_distribution'] ?? []),
                        array_values($restrictionPatterns['hourly_distribution'] ?? [])
                    );
                @endphp
                
                @for($i = 0; $i < min(24, $hours); $i++)
                    @php
                        $hour = now()->subHours($hours - $i - 1)->format('H:00');
                        $bypasses = $bypassPatterns['hourly_distribution'][$hour] ?? 0;
                        $restrictions = $restrictionPatterns['hourly_distribution'][$hour] ?? 0;
                        $total = $bypasses + $restrictions;
                        $height = $maxActivity > 0 ? ($total / $maxActivity) * 100 : 0;
                    @endphp
                    
                    <div class="text-center">
                        <div class="h-16 flex flex-col justify-end mb-1">
                            @if($total > 0)
                                <div class="bg-blue-500 rounded-t" style="height: {{ ($bypasses / $total) * $height }}%" title="Bypasses: {{ $bypasses }}"></div>
                                <div class="bg-red-500 rounded-b" style="height: {{ ($restrictions / $total) * $height }}%" title="Restrictions: {{ $restrictions }}"></div>
                            @else
                                <div class="bg-gray-200 rounded" style="height: 2px;"></div>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 transform -rotate-45 origin-center">
                            {{ $hour }}
                        </div>
                    </div>
                @endfor
            </div>
            <div class="flex justify-center mt-2 space-x-4 text-xs">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded mr-1"></div>
                    <span>Bypasses</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded mr-1"></div>
                    <span>Restrictions</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="mt-6 pt-4 border-t border-gray-200 text-center">
        <p class="text-xs text-gray-500">
            Last updated: {{ now()->format('Y-m-d H:i:s T') }} | 
            <a href="#" onclick="location.reload()" class="text-blue-600 hover:text-blue-800">Refresh</a>
        </p>
    </div>
</div>