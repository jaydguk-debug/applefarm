<?php

namespace common\models;

use ReflectionClass;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "apple".
 *
 * @property int $id
 * @property string $color The color of the apple
 * @property int $created_at Unix timestamp when the apple appeared on the tree
 * @property int|null $fallen_at Unix timestamp when the apple fell from the tree
 * @property int $status Current status: 1 - on_tree, 2 - on_ground, 3 - rotten
 * @property float $eaten_percentage Percentage of the apple that has been eaten
 */
class Apple extends ActiveRecord
{
    const STATUS_ON_TREE = 1;
    const STATUS_ON_GROUND = 2;
    const STATUS_ROTTEN = 3;

    // Time in seconds after which an apple becomes rotten
    const ROTTEN_THRESHOLD = 18000; // 5 hours in sec

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'apple';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['color', 'created_at'], 'required'],
            [['created_at', 'fallen_at'], 'integer'],
            [['eaten_percentage'], 'number', 'min' => 0, 'max' => 100],
            [['color'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_ON_TREE, self::STATUS_ON_GROUND, self::STATUS_ROTTEN]],
            // Ensure eaten_percentage is 0 if status is on_tree
            ['eaten_percentage', 'compare', 'compareValue' => 0, 'operator' => '==', 'when' => function($model) {
                return $model->status === self::STATUS_ON_TREE;
            }, 'message' => 'Eaten percentage must be 0 for apples on the tree.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'color' => 'Color',
            'created_at' => 'Created At',
            'fallen_at' => 'Fallen At',
            'status' => 'Status',
            'eaten_percentage' => 'Eaten Percentage',
        ];
    }

    /**
     * Checks if the apple is currently on the tree.
     * @return bool True if the apple is on the tree, false otherwise.
     */
    public function isOnTree()
    {
        return $this->status === self::STATUS_ON_TREE;
    }

    /**
     * Checks if the apple has fallen from the tree.
     * @return bool True if the apple is on the ground, false otherwise.
     */
    public function isOnGround()
    {
        return $this->status === self::STATUS_ON_GROUND;
    }

    /**
     * Checks if the apple is rotten.
     * @return bool True if the apple is rotten, false otherwise.
     */
    public function isRotten()
    {
        if ($this->status === self::STATUS_ROTTEN) {
            return true;
        }
        // Check if it's on the ground for more than the threshold time
        if ($this->isOnGround() && $this->fallen_at !== null) {
            return (time() - $this->fallen_at) >= self::ROTTEN_THRESHOLD;
        }
        return false;
    }

    /**
     * Checks if the apple is completely eaten (100%).
     * @return bool True if the apple is completely eaten, false otherwise.
     */
    public function isCompletelyEaten()
    {
        return $this->eaten_percentage >= 100;
    }

    /**
     * Makes the apple fall from the tree.
     * Updates the status and sets the fallen_at timestamp.
     * @return bool True if the apple was successfully made to fall, false otherwise.
     */
    public function fall()
    {
        if ($this->isOnTree()) {
            $this->status = self::STATUS_ON_GROUND;
            $this->fallen_at = time();
            return $this->save(false, ['status', 'fallen_at']);
        }
        return false; // Cannot fall if not on the tree
    }

    /**
     * Eats a percentage of the apple.
     * @param float $percent The percentage to eat (e.g., 25.5 for 25.5%).
     * @return bool True if the apple was successfully eaten, false otherwise.
     */
    public function eat($percent)
    {
        if ($percent <= 0) {
            return false; // Cannot eat 0% or negative
        }
        if (!$this->isOnGround()) {
            return false; // Can eat only if on the ground
        }

        $newEatenPercentage = $this->eaten_percentage + $percent;
        if ($newEatenPercentage > 100) {
            $newEatenPercentage = 100; // Cap at 100%
        }

        $this->eaten_percentage = $newEatenPercentage;

        if ($this->isCompletelyEaten()) {
            // Apple is fully eaten, it should be removed later
            // For now, just update the percentage. The controller will handle deletion.
        }

        return $this->save(false, ['eaten_percentage']);
    }

    /**
     * Deletes the apple from the database.
     * @return bool True if the apple was successfully deleted, false otherwise.
     */
    public function remove()
    {
        // This method just calls the standard ActiveRecord delete
        return $this->delete() !== false;
    }

    /**
     * Updates the status of the apple if it has become rotten due to time.
     * Should be called before displaying or interacting with the apple.
     */
    public function checkAndMarkRotten()
    {
        if ($this->isOnGround() && $this->isRotten() && $this->status !== self::STATUS_ROTTEN) {
            $this->status = self::STATUS_ROTTEN;
            $this->save(false, ['status']); // Save only the status field
        }
    }

    /**
     * Generates a random color for the apple.
     * @return string A random color name.
     */
    public static function getRandomColor()
    {
        $colors = ['Red', 'Green', 'Yellow', 'Blue', 'Purple', 'Orange'];
        return $colors[array_rand($colors)];
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        $status = $this->status;
        $statusPrefix = 'STATUS_';
        $statusPrefixLen = strlen($statusPrefix);
        $constants = (new ReflectionClass(Apple::class))->getConstants();
        $statusName = null;
        foreach ($constants as $constantName => $constantValue) {
            if ($constantValue == $status && str_starts_with($constantName, $statusPrefix)) {
                $statusName = substr($constantName, $statusPrefixLen);
            }
        }
        return $statusName ?: $statusPrefix . $status;
    }
}