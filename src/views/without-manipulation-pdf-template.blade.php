<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
            }
            .wrapper {
                margin: 0 -20px 0;
                padding: 0 15px;
            }
            .middle {
                text-align: center;
            }
            .title {
                font-size: 35px;
            }
            .pb-10 {
                padding-bottom: 10px;
            }
            .pb-5 {
                padding-bottom: 5px;
            }
            .head-content{
                padding-bottom: 4px;
                border-style: none none ridge none;
                font-size: 18px;
            }
            thead { display: table-header-group; }
            tfoot { display: table-row-group; }
            tr { page-break-inside: avoid; }
            table.table {
                font-size: 13px;
                border-collapse: collapse;
            }
            .page-break {
                page-break-after: always;
                page-break-inside: avoid;
            }
            tr.even {
                background-color: #eff0f1;
            }
            table .left {
                text-align: left;
            }
            table .right {
                text-align: right;
            }
            table .bold {
                font-weight: 600;
            }
            .bg-black {
                background-color: #000;
            }
            .f-white {
                color: #fff;
            }
        </style>
    </head>
    <body>
        <?php
        $ctr = 1;
        $no = 1;
        $total = [];
        $grandTotalSkip = 1;

        foreach ($showTotalColumns as $column => $type) {
            $total[$column] = 0;
        }

        if ($showTotalColumns != []) {
            foreach ($columns as $colName => $colData) {
                if (!array_key_exists($colName, $showTotalColumns)) {
                    $grandTotalSkip++;
                } else {
                    break;
                }
            }
        }
        ?>
        <div class="wrapper">
            <div class="pb-5">
                <div class="middle pb-10 title">
                    {{ $headers['title'] }}
                </div>
                @if ($showMeta)
                <div class="head-content">
                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                        <?php $metaCtr = 0; ?>
                        @foreach($headers['meta'] as $name => $value)
                            @if ($metaCtr % 2 == 0)
                            <tr>
                            @endif
                                <td><span style="color:#808080;">{{ $name }}</span>: {{ ucwords($value) }}</td>
                            @if ($metaCtr % 2 == 1)
                            </tr>
                            @endif
                            <?php $metaCtr++; ?>
                        @endforeach
                    </table>
                </div>
                @endif
            </div>
            <div class="content">
                <table width="100%" class="table">
                    @if ($showHeader)
                    <thead>
                        <tr>
                            @if ($showNumColumn)
                                <th class="left">No</th>
                            @endif
                            @foreach ($columns as $colName => $colData)
                                <th class="left">{{ $colName }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    @endif
                    <?php
                    $chunkRecordCount = ($limit == null || $limit > 50000) ? 50000 : $limit + 1;
                    $__env = isset($__env) ? $__env : null;
                    $query->chunk($chunkRecordCount, function($results) use(&$ctr, &$no, &$total, $grandTotalSkip, $columns, $limit, $showTotalColumns, $applyFlush, $showNumColumn, $__env) {
                    ?>
                    @foreach($results as $result)
                        <?php if ($limit != null && $ctr == $limit + 1) return false; ?>
                        <tr align="center" class="{{ ($no % 2 == 0) ? 'even' : 'odd' }}">
                            @if ($showNumColumn)
                                <td class="left">{{ $no }}</td>
                            @endif
                            @foreach ($result->toArray() as $rowData)
                                <td class="left">{{ $rowData }}</td>
                            @endforeach
                        </tr>
                        <?php
                            foreach ($columns as $colName => $colData) {
                                if (array_key_exists($colName, $showTotalColumns)) {
                                    $total[$colName] += $result->{$colData};
                                }
                            }
                            $ctr++; $no++;
                        ?>
                    @endforeach
                    <?php
                    if ($applyFlush) flush();
                    });
                    ?>
                    @if ($showTotalColumns != [] && $ctr > 1)
                        <tr class="bg-black f-white">
                            @if ($showNumColumn || $grandTotalSkip > 1)
                                <td colspan="{{ !$showNumColumn ? $grandTotalSkip - 1 : $grandTotalSkip }}"><b>Grand Total</b></td> {{-- For Number --}}
                            @endif
                            <?php $dataFound = false; ?>
                            @foreach ($columns as $colName => $colData)
                                @if (array_key_exists($colName, $showTotalColumns))
                                    <?php $dataFound = true; ?>
                                    @if ($showTotalColumns[$colName] == 'point')
                                        <td class="left"><b>{{ number_format($total[$colName], 2, '.', ',') }}</b></td>
                                    @else
                                        <td class="left"><b>{{ strtoupper($showTotalColumns[$colName]) }} {{ number_format($total[$colName], 2, '.', ',') }}</b></td>
                                    @endif
                                @else
                                    @if ($dataFound)
                                        <td></td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endif
                </table>
            </div>
        </div>
        <script type="text/php">
            @if (strtolower($orientation) == 'portrait')
            if ( isset($pdf) ) {
                $pdf->page_text(30, ($pdf->get_height() - 26.89), "Date Printed: " . date('d M Y H:i:s'), null, 10);
                $pdf->page_text(($pdf->get_width() - 84), ($pdf->get_height() - 26.89), "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10);
            }
            @elseif (strtolower($orientation) == 'landscape')
            if ( isset($pdf) ) {
                $pdf->page_text(30, ($pdf->get_height() - 26.89), "Date Printed: " . date('d M Y H:i:s'), null, 10);
                $pdf->page_text(($pdf->get_width() - 84), ($pdf->get_height() - 26.89), "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10);
            }
            @endif
        </script>
    </body>
</html>
