<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\models\Apple;

/**
 * AppleController handles the backend operations for Apple objects.
 */
class AppleController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // '@' is authorized user
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays the list of apples and handles actions like eat, fall, remove.
     * @return string
     */
    public function actionIndex()
    {
        $apples = Apple::find()->all();

        // Check and update status for each apple before display
        foreach ($apples as $apple) {
            $apple->checkAndMarkRotten();
        }

        return $this->render('index', ['apples' => $apples]);
    }

    /**
     * Generates a specified number of random apples and saves them to the database.
     * @return \yii\web\Response
     */
    public function actionGenerate()
    {
        $count = (int) Yii::$app->request->post('count', 5); // Default to 5 if not specified
        $count = max(1, min(50, $count)); // Limit between 1 and 50 for safety

        for ($i = 0; $i < $count; $i++) {
            $apple = new Apple();
            $apple->color = Apple::getRandomColor();
            // Set created_at to a random time within the last day
            $apple->created_at = time() - rand(0, 86400);
            $apple->status = Apple::STATUS_ON_TREE;
            $apple->eaten_percentage = 0;
            $apple->fallen_at = null;
            $apple->save();
        }

        return $this->redirect(['index']);
    }

    /**
     * Handles the 'fall' action for a specific apple.
     * @param int $id The ID of the apple.
     * @return \yii\web\Response
     */
    public function actionFall($id)
    {
        $apple = $this->findModel($id);

        if ($apple && $apple->fall()) {
            Yii::$app->session->setFlash('success', 'Apple ' . $apple->id . ' has fallen.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to make the apple fall.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Handles the 'eat' action for a specific apple.
     * @param int $id The ID of the apple.
     * @param float $percent The percentage to eat.
     * @return \yii\web\Response
     */
    public function actionEat($id, $percent)
    {
        $apple = $this->findModel($id);

        if ($apple && $apple->eat($percent)) {
            if ($apple->isCompletelyEaten()) {
                $apple->remove(); // Remove if fully eaten
                Yii::$app->session->setFlash('success', 'Apple ' . $apple->id . ' was completely eaten and removed.');
            } else {
                Yii::$app->session->setFlash('success', 'You ate ' . $percent . '% of apple ' . $apple->id . '. Eaten: ' . $apple->eaten_percentage . '%.');
            }
        } else {
            Yii::$app->session->setFlash('error', "Failed to eat the apple $id. It might be on the tree.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Handles the 'remove' action for a specific apple.
     * @param int $id The ID of the apple.
     * @return \yii\web\Response
     */
    public function actionRemove($id)
    {
        $apple = $this->findModel($id);

        if ($apple && $apple->remove()) {
            Yii::$app->session->setFlash('success', 'Apple ' . $apple->id . ' was removed.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to remove the apple.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Simple login page view.
     * @return string|\yii\web\Response
     */
    public function actionLogin()
    {
        if (Yii::$app->request->isPost) {
            $password = Yii::$app->request->post('password');
            $correctPassword = 'your_secret_password'; // Replace with a secure method

            if ($password === $correctPassword) {
                Yii::$app->session->set('backend_password', $correctPassword);
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Incorrect password.');
            }
        }
        return $this->render('login');
    }

    /**
     * Finds the Apple model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id The ID of the apple.
     * @return Apple The loaded model.
     * @throws \yii\web\NotFoundHttpException if the model cannot be found.
     */
    protected function findModel($id)
    {
        if (($model = Apple::findOne($id)) !== null) {
            return $model;
        }

        throw new \yii\web\NotFoundHttpException('The requested apple does not exist.');
    }
}