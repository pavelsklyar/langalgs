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

        $partOfSpeechCount = $textRowsCount - 2; // минус заголовок и сумма
        $phraseTransposeMatrix = $this->transpose($phraseRelativeMatrix);

        for ($wordInPhrase = 1; $wordInPhrase < $phraseRowsCount; $wordInPhrase++) {

            for ($partOfSpeech = 0; $partOfSpeech <= $partOfSpeechCount; $partOfSpeech++) {
                $partOfSpeechValues = []; // массив значений, из которых потом надо будет выбрать максимальное

                for ($i = 0; $i <= $partOfSpeechCount; $i++) {
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

        $partOfSpeechCount = $textRowsCount - 2; // минус заголовок и сумма

        $textTransposeMatrix = $this->transpose($textRelativeMatrix);
        $phraseTransposeMatrix = $this->transpose($phraseRelativeMatrix);

        for ($wordInPhrase = 1; $wordInPhrase < $phraseRowsCount; $wordInPhrase++) {

            for ($partOfSpeech = 0; $partOfSpeech <= $partOfSpeechCount; $partOfSpeech++) {
                $sum = 0;
                $multiplier = $phraseTransposeMatrix[$partOfSpeech + 1][$wordInPhrase];

                for ($i = 0; $i <= $partOfSpeechCount; $i++) {
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
