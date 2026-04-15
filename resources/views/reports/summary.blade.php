<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Report Summary</title>
    <style>
      body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
      .header { margin-bottom: 14px; }
      .title { font-size: 18px; font-weight: 700; margin: 0 0 4px 0; }
      .meta { font-size: 11px; color: #4b5563; margin: 0; }
      .section { margin-top: 16px; }
      .section h2 { font-size: 13px; margin: 0 0 8px 0; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
      th { background: #f9fafb; font-weight: 700; }
      .right { text-align: right; }
      .muted { color: #6b7280; }
    </style>
  </head>
  <body>
    <div class="header">
      <p class="title">Report Summary</p>
      <p class="meta">
        Period:
        @if(!empty($from) && !empty($to))
          {{ $from }} to {{ $to }}
        @elseif(!empty($from))
          From {{ $from }}
        @elseif(!empty($to))
          Up to {{ $to }}
        @else
          All time
        @endif
        <span class="muted">· Generated {{ $generatedAt?->format('Y-m-d H:i') }}</span>
      </p>
    </div>

    <div class="section">
      <h2>Enrollments by Program</h2>
      <table>
        <thead>
          <tr>
            <th style="width: 18%;">Program Code</th>
            <th>Program Name</th>
            <th style="width: 18%;" class="right">Total Enrollments</th>
          </tr>
        </thead>
        <tbody>
          @forelse($enrollmentsByProgram as $row)
            <tr>
              <td>{{ $row->program_code }}</td>
              <td>{{ $row->program_name }}</td>
              <td class="right">{{ (int) ($row->total ?? 0) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">No records found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="section">
      <h2>Updates by Status</h2>
      <table>
        <thead>
          <tr>
            <th>Status</th>
            <th style="width: 18%;" class="right">Update Count</th>
            <th style="width: 26%;" class="right">Total Amount Received</th>
          </tr>
        </thead>
        <tbody>
          @forelse($updatesByStatus as $row)
            <tr>
              <td>{{ $row->status_label }}</td>
              <td class="right">{{ (int) ($row->total ?? 0) }}</td>
              <td class="right">{{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="muted">No records found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </body>
</html>

