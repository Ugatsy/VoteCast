# TODO - Voter Tracking Feature (VoteCast)

## Step 1 — Backend: VotingSession model
- [x] Add `getEligibleVotersWithStatus(): array`
- [x] Add `getVoteStatisticsAttribute(): array`

## Step 2 — Backend: Admin VotingSessionController
- [ ] Add `getVoters(VotingSession $votingSession, Request $request)` AJAX endpoint (status/search filters + pagination + statistics)

## Step 3 — Backend: ExportController
- [ ] Add `exportVoters(VotingSession $votingSession, Request $request)` CSV exporter
- [ ] Add export route

## Step 4 — Routing
- [ ] Register AJAX voters route: `/admin/sessions/{votingSession}/voters`
- [ ] Register CSV export route: `/admin/sessions/{votingSession}/voters/export`

## Step 5 — Frontend: admin sessions show page
- [ ] Insert “Voter Tracking” section into `resources/views/admin/sessions/show.blade.php`
- [ ] Implement vanilla JS to:
  - load voters via AJAX
  - apply status filter + search
  - handle pagination
  - export CSV using current filters

## Step 6 — Critical-path manual verification (after implementation)
- [ ] Open an admin election “show” page
- [ ] Click Refresh / change filters / paginate and verify table + statistics update
- [ ] Verify CSV export downloads correct rows for current filters
- [ ] Ensure no JS runtime errors on the page
