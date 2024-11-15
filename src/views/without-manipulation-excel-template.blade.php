<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <style>
            .center { text-align: center; }
            table .left { text-align: left; }
            table .right { text-align: right; }
            table .bold { font-weight: 600; }
            .bg-black { background-color: #000; }
            .f-white { color: #fff; }
        </style>
    </head>
    <body>
        <?php
        $ctr = 1;
        $no = 1;
        $total = array_map(function () { return 0; }, $showTotalColumns);
        $avg = array_map(function () { return 0; }, $showAvgColumns);
        $aggregateColSkip = 1;
        $isOnSameGroup = true;
        $currentGroupByData = [];

        if (count($showTotalColumns) > 0 || count($showAvgColumns) > 0) {
            foreach ($columns as $colName => $colData) {
                if (!array_key_exists($colName, $showTotalColumns) && !array_key_exists($colName, $showAvgColumns)) {
                    $aggregateColSkip++;
                } else {
                    break;
                }
            }
        }

        $aggregateColSkip = !$showNumColumn ? $aggregateColSkip - 1 : $aggregateColSkip;

        function renderAggregateRow ($showAvgColumns) use($aggregateColSkip) {
            if ($showAvgColumns != []) return '';

            return "
                <tr class='f-white'>
                    <td colspan='{$aggregateColSkip}' class='bg-black'><b>{$avgLabel}</b></td>
                <?php $dataFound = false; ?>
                    @foreach ($columns as $colName => $colData)
                        @if (array_key_exists($colName, $showAvgColumns))
                            <?php
                            $dataFound = true;
                            $avgFormat = $showAvgColumns[$colName];
                            $avg = round($total[$colName] / $no, 2);
                            ?>
                            @if ($avgFormat instanceof Closure)
                                <td class='bg-black left'><b>{{ $function($avg) }}</b></td>
                            @elseif ($avgFormat == 'point')
                                <td class='bg-black left'><b>{{ number_format($avg, 2) }}</b></td>
                            @else
                                <td class='bg-black left'><b>{{ strtoupper($avgFormat) }} {{ number_format($avg, 2) }}</b></td>
                            @endif
                        @else
                            @if ($dataFound)
                                <td class='bg-black'></td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            ";
        }
        ?>
        <table>
            <tr>
                <td colspan="{{ count($columns) + 1 }}" class="center"><h1>{{ $headers['title'] }}</h1></td>
            </tr>
            @if ($showMeta)
                @foreach($headers['meta'] as $name => $value)
                    <tr>
                        <td><b>{{ $name }}</b></td>
                        <td colspan="{{ count($columns) }}">{{ ucwords($value) }}</td>
                    </tr>
                @endforeach
            @endif
        </table>
        <table>
            @if ($showHeader)
            <thead>
                <tr>
                    @if ($showNumColumn)
                        <th class="left">{{ __('laravel-report-generator::messages.no') }}</th>
                    @endif
                    @foreach ($columns as $colName => $colData)
                        <th class="left">{{ $colName }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @endif
            <?php
            $__env = isset($__env) ? $__env : null;
            ?>
            @foreach($query->take($limit ?: null)->cursor() as $result)
                <?php
                    if ($groupByArr != []) {
                        $isOnSameGroup = true;
                        foreach ($groupByArr as $groupBy) {
                            if (is_object($columns[$groupBy]) && $columns[$groupBy] instanceof Closure) {
                                $thisGroupByData[$groupBy] = $columns[$groupBy]($result);
                            } else {
                                $thisGroupByData[$groupBy] = $result->{$columns[$groupBy]};
                            }

                            if (isset($currentGroupByData[$groupBy])) {
                                if ($thisGroupByData[$groupBy] != $currentGroupByData[$groupBy]) {
                                    $isOnSameGroup = false;
                                }
                            }

                            $currentGroupByData[$groupBy] = $thisGroupByData[$groupBy];
                        }

                        if ($isOnSameGroup === false) {
                            echo '<tr class="f-white">';
                            if ($showNumColumn || $aggregateColSkip > 1) {
                                echo '<td class="bg-black" colspan="' . $aggregateColSkip . '"><b>'.__('laravel-report-generator::messages.grand_total').'</b></td>';
                            }
                            $dataFound = false;
                            foreach ($columns as $colName => $colData) {
                                if (array_key_exists($colName, $showTotalColumns)) {
                                    if ($showTotalColumns[$colName] == 'point') {
                                        echo '<td class="left bg-black"><b>' . number_format($total[$colName], 2, '.', ',') . '</b></td>';
                                    } else {
                                        echo '<td class="left bg-black"><b>' . strtoupper($showTotalColumns[$colName]) . ' ' . number_format($total[$colName], 2, '.', ',') . '</b></td>';
                                    }
                                    $dataFound = true;
                                } else {
                                    if ($dataFound) {
                                        echo '<td class="bg-black"></td>';
                                    }
                                }
                            }
                            echo '</tr>';//<tr style="height: 10px;"><td colspan="99">&nbsp;</td></tr>';

                            // Reset No, Reset Grand Total
                            $no = 1;
                            foreach ($showTotalColumns as $showTotalColumn => $type) {
                                $total[$showTotalColumn] = 0;
                            }
                            $isOnSameGroup = true;
                        }
                    }
                ?>
                <tr align="center">
                    @if ($showNumColumn)
                        <td class="left">{{ $no }}</td>
                    @endif
                    <?php
                        $data = $result->toArray();
                        if (count($data) > count($columns)) array_pop($data);
                    ?>
                    @foreach ($data as $rowData)
                        <td class="left">{{ $rowData }}</td>
                    @endforeach
                </tr>
                <?php
                    foreach ($showTotalColumns as $colName => $type) {
                        $total[$colName] += $result->{$columns[$colName]};
                    }
                    $ctr++; $no++;
                ?>
            @endforeach
            @if ($showTotalColumns != [] && $ctr > 1)
                <tr class="f-white">
                    <td colspan="{{ $aggregateColSkip }}" class="bg-black"><b>{{ $totalLabel }}</b></td> {{-- For Number --}}
                    <?php $dataFound = false; ?>
                    @foreach ($columns as $colName => $colData)
                        @if (array_key_exists($colName, $showTotalColumns))
                            <?php $dataFound = true; $totalFormat = $showTotalColumns[$colName]; ?>
                            @if ($totalFormat instanceof Closure)
                                <td class="bg-black left"><b>{{ $totalFormat($total[$colName]) }}</b></td>
                            @elseif ($totalFormat == 'point')
                                <td class="bg-black left"><b>{{ number_format($total[$colName], 2) }}</b></td>
                            @else
                                <td class="bg-black left"><b>{{ strtoupper($totalFormat) }} {{ number_format($total[$colName], 2) }}</b></td>
                            @endif
                        @else
                            @if ($dataFound)
                                <td class="bg-black"></td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endif
            @if ($showAvgColumns != [] && $ctr > 1)
                <tr class="f-white">
                    <td colspan="{{ $aggregateColSkip }}" class="bg-black"><b>{{ $avgLabel }}</b></td> {{-- For Number --}}
                    <?php $dataFound = false; ?>
                    @foreach ($columns as $colName => $colData)
                        @if (array_key_exists($colName, $showAvgColumns))
                            <?php
                                $dataFound = true;
                                $avgFormat = $showAvgColumns[$colName];
                                $avg = round($total[$colName] / $no, 2);
                            ?>
                            @if ($avgFormat instanceof Closure)
                                <td class="bg-black left"><b>{{ $function($avg) }}</b></td>
                            @elseif ($avgFormat == 'point')
                                <td class="bg-black left"><b>{{ number_format($avg, 2) }}</b></td>
                            @else
                                <td class="bg-black left"><b>{{ strtoupper($avgFormat) }} {{ number_format($avg, 2) }}</b></td>
                            @endif
                        @else
                            @if ($dataFound)
                                <td class="bg-black"></td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endif
            </tbody>
        </table>
    </body>
</html>
