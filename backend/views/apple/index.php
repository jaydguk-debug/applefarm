<?php

use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $apples common\models\Apple[] */

$this->title = 'Apples';
?>
<div class="apple-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php $form = ActiveForm::begin(['action' => ['generate'], 'method' => 'post']); ?>
        <?= $form->field(new \yii\base\DynamicModel(['count' => '']), 'count')->textInput(['type' => 'number', 'min' => '1', 'max' => '50', 'value' => '5'])->label('Number of Apples to Generate') ?>
        <?= Html::submitButton('Generate Apples', ['class' => 'btn btn-success']) ?>
        <?php ActiveForm::end(); ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => new ArrayDataProvider([
            'allModels' => $apples,
            'pagination' => false,
            'sort' => false,
        ]),
        'columns' => [
            [
                'attribute' => 'id',
                'contentOptions' => ['style' => 'width: 50px;'],
            ],
            [
                'attribute' => 'color',
                'contentOptions' => function ($model) {
                    // Optional: Color the cell background based on the apple's color
                    $colorMap = [
                        'Red' => 'red',
                        'Green' => 'green',
                        'Yellow' => 'yellow',
                        'Blue' => 'blue',
                        'Purple' => 'purple',
                        'Orange' => 'orange',
                    ];
                    $bgColor = isset($colorMap[$model->color]) ? $colorMap[$model->color] : 'lightgray';
                    return ['style' => "background-color: $bgColor;"];
                },
            ],
            [
                'attribute' => 'created_at',
                'value' => function ($model) {
                    return date('Y-m-d H:i:s', $model->created_at);
                },
            ],
            [
                'attribute' => 'fallen_at',
                'value' => function ($model) {
                    return $model->fallen_at ? date('Y-m-d H:i:s', $model->fallen_at) : 'Not fallen';
                },
            ],
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    $status = $model->getStatusName();
                    if ($model->isRotten()) {
                        $status = 'ROTTEN'; // Override status display if rotten check passes
                    }
                    return $status;
                },
            ],
            [
                'attribute' => 'eaten_percentage',
                'value' => function ($model) {
                    return $model->eaten_percentage . '%';
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{fall} {eat} {remove}',
                'buttons' => [
                    'fall' => function ($url, $model, $key) {
                        if ($model->isOnTree() && !$model->isRotten() && !$model->isCompletelyEaten()) {
                            return Html::a('Fall', ['fall', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-warning',
                                'data' => [
                                    'method' => 'post',
                                ],
                            ]);
                        }
                        return Html::tag('span', 'Fall', ['class' => 'btn btn-sm btn-default disabled']);
                    },
                    'eat' => function ($url, $model, $key) {
                        if ($model->isOnGround() && !$model->isRotten() && !$model->isCompletelyEaten()) {
                            $form = Html::beginForm(['eat', 'id' => $model->id], 'get', [
                                'style' => 'display:inline-block; margin:0 5px;'
                            ]);
                            $form .= Html::input('number', 'percent', 10, [
                                'min' => '1',
                                'max' => '100',
                                'style' => 'width:60px; margin-right:5px; vertical-align: middle;',
                                'required' => true
                            ]);
                            $form .= Html::submitButton('Eat %', ['class' => 'btn btn-sm btn-info']);
                            $form .= Html::endForm();
                            return $form;
                        }
                        return null;
                    },
                    'remove' => function ($url, $model, $key) {
                        return Html::a('Remove', ['remove', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-danger',
                            'data' => [
                                'confirm' => 'Are you sure you want to remove this apple?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>


</div>