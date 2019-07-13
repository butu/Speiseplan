var mealList = {
    addMeal: function (mealId) {
        var meals = this.getAllMeals();
        meals[mealId] = true;
        this.saveMeals(meals);
    },
    removeMeal: function (mealId) {
        var meals = this.getAllMeals();
        delete meals[mealId];
        this.saveMeals(meals);
    },
    getAllMeals: function () {
        var mealsString = window.localStorage.getItem('meals');
        if (mealsString) {
            return JSON.parse(mealsString);
        } else {
            return {};
        }
    },
    marked: function (mealId) {
        var meals = this.getAllMeals();
        return meals[mealId];
    },
    saveMeals: function (meals) {
        window.localStorage.setItem('meals', JSON.stringify(meals));
    },
    render: function () {
        var meals = this.getAllMeals();
        $('.meal').each(function (index, meal) {
            var mealId = $(meal).attr('data-id');
            if (meals[mealId]) {
                $(this).addClass('marked');
            } else {
                $(this).removeClass('marked');
            }
        });
        $('.day').each(function (index, day) {
            if ($(day).find('.meal.marked').length > 0) {
                $(day).addClass('marked');
            } else {
                $(day).removeClass('marked');
            }
        });
    }
};

mealList.render();

$('button.mark').on('click', function (event) {
    var mealId = $(this).closest('.meal').attr('data-id');
    if (mealList.marked(mealId)) {
        mealList.removeMeal(mealId);
    } else {
        mealList.addMeal(mealId);
    }
    mealList.render();
});