<?php

declare(strict_types=1);

namespace App\Model\Language\Console;

use Illuminate\Console\Command;

final class CalcLangCommand extends Command
{
    protected $signature = 'app:lang:calc {--text=} {--phrase=}';

    public function __invoke(): void
    {
        /**
         * Загрузка матриц из переданных файлов.
         */
        $textFile = $this->option('text');
        $textMatrix = $this->loadMatrix($textFile);

        $this->line('Загружена матрица текста:');
        $this->writeMatrix($textMatrix);

        $phraseFile = $this->option('phrase');
        $phraseMatrix = $this->loadMatrix($phraseFile);

        $this->line('Загружена матрица фразы:');
        $this->writeMatrix($phraseMatrix);

        /**
         * Построение относительной матрицы.
         */
        $textRelativeMatrix = $this->getRelativeMatrix($textMatrix);
        $this->line('Получена относительная матрица текста:');
        $this->writeMatrix($textRelativeMatrix);

        $phraseRelativeMatrix = $this->getRelativeMatrix($phraseMatrix);
        $this->line('Получена относительная матрица фразы:');
        $this->writeMatrix($phraseRelativeMatrix);

        /**
         * Построение viterbi.
         */
        $viterbi = $this->getViterbi($textRelativeMatrix, $phraseRelativeMatrix);
        $this->line('Получена матрица viterbi:');
        $this->writeMatrix($viterbi);

        /**
         * Построение forward.
         */
        $forward = $this->getForward($textRelativeMatrix, $phraseRelativeMatrix);
        $this->line('Получена матрица forward:');
        $this->writeMatrix($forward);

        /**
         * Построение backward.
         */
        $backward = $this->getBackward($textRelativeMatrix, $phraseRelativeMatrix);
        $this->line('Получена матрица backward:');
        $this->writeMatrix($backward);

        $this->line('Начинаем строить BW.');
        $this->newLine();
        $this->calcWB($textRelativeMatrix, $phraseRelativeMatrix, $forward, $backward);
    }

    /**
     * Загрузка матрицы из csv-файла.
     */
    private function loadMatrix(string $filename): array
    {
        $matrix = [];

        foreach (file($filename) as $key => $line) {
            $lineData = str_getcsv($line);
            $lineData[] = $key === 0 ? 'Сумма' : array_sum($lineData);

            $matrix[] = $lineData;
        }

        return $matrix;
    }

    /**
     * Получение относительной матрицы.
     *
     * Значение каждой ячейки (кроме информативных текстовых)
     * делится на сумму всех занчений в строке (последний элемент
     * строки-массива)
     */
    private function getRelativeMatrix(array $matrix): array
    {
        // чтобы сохранить заголовки, просто копируем и потом обновим значения
        $relativeMatrix = $matrix;
        $rowsCount = count($matrix);

        for ($i = 1; $i < $rowsCount; $i++) {
            $rowLength = count($matrix[$i]);

            $sum = 0;
            // $rowLength - 1 потому что последний элемент - сумма
            for ($j = 1; $j < $rowLength - 1; $j++) {
                $value = $matrix[$i][$j] / $matrix[$i][$rowLength - 1];

                $sum += $value;
                $relativeMatrix[$i][$j] = $value;
            }

            $relativeMatrix[$i][$rowLength - 1] = $sum;
        }

        return $relativeMatrix;
    }

    /**
     * Построение матрицы viterbi.
     */
    private function getViterbi(array $textRelativeMatrix, array $phraseRelativeMatrix): array
    {
        $headers = ['viterbi', 'start'];

        $phraseRowsCount = count($phraseRelativeMatrix);
        for ($i = 1; $i < $phraseRowsCount; $i++) {
            $headers[] = $phraseRelativeMatrix[$i][0];
        }

        $partOfSpeechMatrix = [];
        $textRowsCount = count($textRelativeMatrix);

        for ($i = 1; $i < $textRowsCount; $i++) {
            $partOfSpeech = $textRelativeMatrix[$i][0]; // первый элемент каждой строки - часть речи
            $partOfSpeechMatrix[0][] = $partOfSpeech;
            $partOfSpeechMatrix[1][] = 1;
        }

        $partOfSpeechCount = $textRowsCount - 1; // минус заголовок
        $phraseTransposeMatrix = $this->transpose($phraseRelativeMatrix);

        for ($wordInPhrase = 1; $wordInPhrase < $phraseRowsCount; $wordInPhrase++) {

            for ($partOfSpeech = 0; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
                $partOfSpeechValues = []; // массив значений, из которых потом надо будет выбрать максимальное

                for ($i = 0; $i < $partOfSpeechCount; $i++) {
                    $first = $partOfSpeechMatrix[$wordInPhrase][$i];
                    $second = $textRelativeMatrix[$i + 1][$partOfSpeech + 1];
                    $third = $phraseTransposeMatrix[$partOfSpeech + 1][$wordInPhrase];

                    $partOfSpeechValues[] = round($first * $second * $third, 10);
                }

                $partOfSpeechMatrix[$wordInPhrase + 1][$partOfSpeech] = max($partOfSpeechValues);
            }
        }

        $partOfSpeechMatrix = $this->transpose($partOfSpeechMatrix);

        return [
            $headers,
            ...$partOfSpeechMatrix
        ];
    }

    /**
     * Построение матрицы forward.
     */
    private function getForward(array $textRelativeMatrix, array $phraseRelativeMatrix): array
    {
        $headers = ['forward', 'start'];

        $phraseRowsCount = count($phraseRelativeMatrix);
        for ($i = 1; $i < $phraseRowsCount; $i++) {
            $headers[] = $phraseRelativeMatrix[$i][0];
        }

        $partOfSpeechMatrix = [];
        $textRowsCount = count($textRelativeMatrix);

        for ($i = 1; $i < $textRowsCount; $i++) {
            $partOfSpeech = $textRelativeMatrix[$i][0]; // первый элемент каждой строки - часть речи
            $partOfSpeechMatrix[0][] = $partOfSpeech;
            $partOfSpeechMatrix[1][] = 1;
        }

        $partOfSpeechCount = $textRowsCount - 1; // минус заголовок

        $textTransposeMatrix = $this->transpose($textRelativeMatrix);
        $phraseTransposeMatrix = $this->transpose($phraseRelativeMatrix);

        for ($wordInPhrase = 1; $wordInPhrase < $phraseRowsCount; $wordInPhrase++) {

            for ($partOfSpeech = 0; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
                $sum = 0;
                $multiplier = $phraseTransposeMatrix[$partOfSpeech + 1][$wordInPhrase];

                for ($i = 0; $i < $partOfSpeechCount; $i++) {
                    $first = $partOfSpeechMatrix[$wordInPhrase][$i];
                    $second = $textTransposeMatrix[$partOfSpeech + 1][$i + 1];

                    $sum += $first * $second;
                }

                $partOfSpeechMatrix[$wordInPhrase + 1][$partOfSpeech] = $multiplier * $sum;
            }
        }

        $partOfSpeechMatrix = $this->transpose($partOfSpeechMatrix);

        return [
            $headers,
            ...$partOfSpeechMatrix
        ];
    }

    /**
     * Построение матрицы backward.
     */
    public function getBackward(array $textRelativeMatrix, array $phraseRelativeMatrix): array
    {
        $headers = ['backward', 'start'];

        $phraseRowsCount = count($phraseRelativeMatrix);
        for ($i = 1; $i < $phraseRowsCount; $i++) {
            $headers[] = $phraseRelativeMatrix[$i][0];
        }

        $partOfSpeechMatrix = [];
        $textRowsCount = count($textRelativeMatrix);

        for ($i = 1; $i < $textRowsCount; $i++) {
            $partOfSpeech = $textRelativeMatrix[$i][0]; // первый элемент каждой строки - часть речи
            $partOfSpeechMatrix[0][] = $partOfSpeech;
            $partOfSpeechMatrix[1][] = '-';
        }

        $partOfSpeechCount = $textRowsCount - 1; // минус заголовок
        $phraseTransposeMatrix = $this->transpose($phraseRelativeMatrix);

        for ($partOfSpeech = 0; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
            $partOfSpeechMatrix[$phraseRowsCount][$partOfSpeech] = 1;
        }

        for ($wordInPhrase = $phraseRowsCount - 1; $wordInPhrase > 1; $wordInPhrase--) {
            for ($partOfSpeech = 0; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
                $sum = 0;

                for ($i = 0; $i < $partOfSpeechCount; $i++) {
                    $first = $partOfSpeechMatrix[$wordInPhrase + 1][$i];
                    $second = $textRelativeMatrix[$partOfSpeech + 1][$i + 1];
                    $third = $phraseTransposeMatrix[$i + 1][$wordInPhrase];

                    $sum += $first * $second * $third;
                }

                $partOfSpeechMatrix[$wordInPhrase][$partOfSpeech] = $sum;
            }
        }

        ksort($partOfSpeechMatrix);

        $partOfSpeechMatrix = $this->transpose($partOfSpeechMatrix);

        return [
            $headers,
            ...$partOfSpeechMatrix
        ];
    }

    /**
     * Построение матриц BW.
     *
     * 1. Строим гамму.
     * 2. Проходим по всем словам без последнего в цикле
     * 3. Для каждого этого слова формируем ksi матрицу. Она квадратная, сторона равна количеству частей речи.
     * 4. Проходим по каждой части речи ($partOfSpeech) для формирования матрицы. Это вертикальная сторона, там
     * будет 10 строк.
     * 5. Снова проходим по каждой части речи ($rowPartOfSpeech) и формируем строку ($ksiRow), это горизонтальная
     * строка в ksi_ij в гугл доке. Её размер равен количеству частей речи, поэтому снова по частям речи идём.
     * 6. Построив матрицу $ksi, нужно посчитать сумму её элементов и каждый элемент разделить на эту сумму.
     * Чтобы не считать сумму отдельно, я считаю её сразу при вычислении $ksiRow (переменная $ksiSum). Далее
     * я прохожу по каждой строке и по каждому столбцу через встроенную функцию array_map() для изменения значений
     * в массиве. После построения вывожу все полученные таблицы с добавлением заголовков - частей речи.
     * 7. Вычисляем a_ij, она достаточно простая, опять проходим по частям речи и во вложенном цикле по частям речи
     * снова и берём значения из каждой ksi по части речи и из гаммы.
     * 8. Вычисляем b_i, принцип тот же, что в a_ij, только числитель формируется из транспонированной гаммы.
     */
    public function calcWB(array $textRelativeMatrix, array $phraseRelativeMatrix, array $forward, array $backward): void
    {
        $partOfSpeechCount = count($textRelativeMatrix);
        $phraseWordsCount = count($phraseRelativeMatrix);

        $transposeBackward = $this->transpose($backward);

        $gamma = $this->getGamma($textRelativeMatrix, $phraseRelativeMatrix, $forward, $backward);
        $this->line('Построена матрица gamma:');
        $this->writeMatrix($gamma);

        /*
         * Вычисляем ksi матрицы.
         */
        $ksiMatrixes =[];

        for ($word = 1; $word < $phraseWordsCount - 1; $word++) {
            $ksi = [];
            $ksiSum = 0;

            for ($partOfSpeech = 1; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
                $ksiRow = [];

                for ($rowPartOfSpeech = 2; $rowPartOfSpeech < $partOfSpeechCount + 1; $rowPartOfSpeech++) {
                    $forwardItem = $forward[$partOfSpeech][$word + 1];
                    $textRelativeItem = $textRelativeMatrix[$partOfSpeech][$rowPartOfSpeech - 1];
                    $transposeBackwardItem = $transposeBackward[$word + 2][$rowPartOfSpeech - 1];
                    $phraseRelativeItem = $phraseRelativeMatrix[$word + 1][$rowPartOfSpeech - 1];

                    $value = $forwardItem * $textRelativeItem * $transposeBackwardItem * $phraseRelativeItem;
                    $ksiRow[] = $value;
                    $ksiSum += $value;
                }

                $ksi[] = $ksiRow;
            }

            $ksi = array_map(static function (array $row) use ($ksiSum): array {
                return array_map(static fn (float $value): float => $value / $ksiSum, $row);
            }, $ksi);

            $ksiMatrixes[] = $ksi;
        }

        // чтобы вывелись в отдельном столбце части речи
        $partOfSpeeches = [];
        for ($partOfSpeech = 1; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
            $partOfSpeeches[] = $textRelativeMatrix[0][$partOfSpeech];
        }

        foreach ($ksiMatrixes as $key => $ksiMatrix) {
            $this->writeKsi($ksiMatrix, $key + 1, $partOfSpeeches);
        }

        /*
         * Вычисляем a_ij матрицу.
         */
        $ksiCount = count($ksiMatrixes);
        $aijMatrix = [];
        for ($partOfSpeech = 0; $partOfSpeech < $partOfSpeechCount - 1; $partOfSpeech++) {
            $aijRow = [];

            for ($rowPartOfSpeech = 0; $rowPartOfSpeech < $partOfSpeechCount - 1; $rowPartOfSpeech++) {
                $numerator = 0;

                for ($ksiNumber = 0; $ksiNumber < $ksiCount; $ksiNumber++) {
                    $numerator += $ksiMatrixes[$ksiNumber][$partOfSpeech][$rowPartOfSpeech];
                }

                $lastGammaWordKey = array_key_last($gamma[$partOfSpeech + 1]);

                // числитель - это сумма строки в гамме для конкретной части речи без последней колонки (последнего слова)
                $denominator = array_sum($gamma[$partOfSpeech + 1]) - $gamma[$partOfSpeech + 1][$lastGammaWordKey];

                $aijRow[] = $denominator === 0.0
                    ? '-'
                    : $numerator / $denominator;
            }

            $aijMatrix[] = $aijRow;
        }

        $this->writeAIJ($aijMatrix, $partOfSpeeches);

        /*
         * Вычисляем b_i матрицу.
         */
        $transposeGamma = $this->transpose($gamma);
        $biMatrix = [];

        for ($word = 1; $word < $phraseWordsCount - 1; $word++) {
            $row = [];

            for ($partOfSpeech = 1; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
                $lastGammaWordKey = array_key_last($gamma[$partOfSpeech]);

                $numerator = $transposeGamma[$word][$partOfSpeech];
                $denominator = array_sum($gamma[$partOfSpeech]) - $gamma[$partOfSpeech][$lastGammaWordKey];

                $row[] = $denominator === 0.0
                    ? "-"
                    : $numerator / $denominator;
            }

            $biMatrix[] = $row;
        }

        $phrase = [];
        for ($i = 1; $i < $phraseWordsCount; $i++) {
            $phrase[] = $phraseRelativeMatrix[$i][0];
        }

        $this->writeBI($biMatrix, $partOfSpeeches, $phrase);
    }

    private function getGamma(array $textRelativeMatrix, array $phraseRelativeMatrix, array $forward, array $backward): array
    {
        $partOfSpeechCount = count($textRelativeMatrix);
        $phraseWordsCount = count($phraseRelativeMatrix);

        $transposeForward = $this->transpose($forward);
        $transposeBackward = $this->transpose($backward);

        // чтобы вывелись в отдельном столбце части речи
        $partOfSpeeches = [];
        for ($partOfSpeech = 1; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
            $partOfSpeeches[] = $textRelativeMatrix[0][$partOfSpeech];
        }

        $gamma[0] = ['gamma', ...$partOfSpeeches];

        for ($word = 0; $word < $phraseWordsCount - 1; $word++) {
            $wordPartOfSpeech = [];

            for ($partOfSpeech = 1; $partOfSpeech < $partOfSpeechCount; $partOfSpeech++) {
                $rowNumber = $word + 2;

                // знаменатель
                $denominator = 0;
                for ($i = 1; $i < $partOfSpeechCount; $i++) {
                    $forwardValue = $transposeForward[$rowNumber][$i];
                    $backwardValue = $transposeBackward[$rowNumber][$i];

                    $denominator += $forwardValue * $backwardValue;
                }

                // числитель
                $numerator = $transposeForward[$rowNumber][$partOfSpeech] * $transposeBackward[$rowNumber][$partOfSpeech];

                $wordPartOfSpeech[] = $numerator / $denominator;
            }

            $gamma[$word + 1] = [$phraseRelativeMatrix[$word + 1][0], ...$wordPartOfSpeech];
        }

        return $this->transpose($gamma);
    }

    /**
     * Отрисовка ksi матрицы с добавлением частей речи.
     */
    private function writeKsi(array $matrix, int $number, array $partOfSpeeches): void
    {
        foreach ($matrix as $key => $row) {
            $matrix[$key] = [$partOfSpeeches[$key], ...$row];
        }

        $matrix[-1] = [sprintf('ksi_ij (%s)', $number), ...$partOfSpeeches];
        ksort($matrix);

        $this->line(sprintf('Построена матрица ksi_ij (%d):', $number));
        $this->writeMatrix($matrix);
    }

    /**
     * Отрисовка aij матрицы с добавлением частей речи.
     */
    private function writeAIJ(array $matrix, array $partOfSpeeches): void
    {
        foreach ($matrix as $key => $row) {
            $matrix[$key] = [$partOfSpeeches[$key], ...$row];
        }

        $matrix[-1] = ['a_ij', ...$partOfSpeeches];
        ksort($matrix);

        $this->line('Построена матрица a_ij:');
        $this->writeMatrix($matrix);
    }

    /**
     * Отрисовка aij матрицы с добавлением частей речи.
     */
    private function writeBI(array $matrix, array $partOfSpeeches, array $phrase): void
    {
        foreach ($matrix as $key => $row) {
            $matrix[$key] = [$phrase[$key], ...$row];
        }

        $matrix[-1] = ['b_i', ...$partOfSpeeches];
        ksort($matrix);

        $this->line('Построена матрица a_ij:');
        $this->writeMatrix($matrix);
    }

    private function writeMatrix(array $matrix): void
    {
        $headers = array_shift($matrix);

        $this->table($headers, $matrix);
        $this->newLine();
    }

    /**
     * Транспонирование матрицы.
     */
    private function transpose(array $matrix): array
    {
        return array_map(null, ...$matrix);
    }
}
