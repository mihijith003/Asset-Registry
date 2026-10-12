<x-app-layout>
    <x-slot name="header">Activity Logs</x-slot>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>System Activity Logs</h3>
                <p>Audit trail of user actions and system events.</p>
            </div>
            <span style="font-size: 0.875rem; color: var(--text-muted);">
                Total: {{ $activityLogs->total() }}
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityLogs as $log)
                        <tr>
                            <td style="font-size: 0.875rem; color: var(--text-muted); white-space: nowrap;">
                                {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-' }}
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    {{ $log->user->name ?? 'System' }}
                                </div>
                                @if(isset($log->user->email))
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $log->user->email }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary);">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td style="max-width: 400px; word-break: break-word;">
                                {{ $log->description ?? '-' }}
                            </td>
                            <td>
                                @if ($log->status == 1)
                                    <span class="badge badge-success">Success</span>
                                @else
                                    <span class="badge badge-danger">Failed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activityLogs->hasPages())
            <div class="table-pagination">
                {{ $activityLogs->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

