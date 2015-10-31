var app = angular.module('myApp', []);


app.factory("scoreService", ['$http', function($http) {

  var serviceBase = 'scoreService/'
    var obj = {};

    obj.getPlayers = function(){
        return $http.get(serviceBase + 'players');
    }

    obj.getCountry = function(){
        return $http.get(serviceBase + 'country');
    }

    obj.insertPlayer = function (player) {
      return $http.post(serviceBase + 'insertPlayer', player).then(function (results) {
          return results;
      });
    }

    obj.updatePlayer = function (id,player) {
      return $http.post(serviceBase + 'updatePlayer', {id:id, player:player}).then(function (status) {
          return status.data;
      });
    }

    return obj;   
}]);

app.service('uniqueId', function() {
    var uniqueId = function() {
      var d = new Date().getTime();
        var uuid = 'xxxxxxxx-xxxx-3xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = (d + Math.random()*16)%16 | 0;
            d = Math.floor(d/16);
            return (c=='x' ? r : (r&0x3|0x8)).toString(16);
        });
        return uuid;
    }
    return uniqueId;
});

app.controller('listCtrl', function ($scope, $timeout, uniqueId, scoreService) {

  $scope.player = {};
  $scope.player.score = 0;
  $scope.player.id = uniqueId();


  
  $scope.range = function(n){
    return new Array(n);
  }


  $scope.scrollLiveTable = function() {
    var scrollerHeight = $("#nano-scoller-1").height();
    var topScrollPosition = $(".score-table__row.me").offset().top;
    console.log(scrollerHeight);

    $("#nano-scoller-1").nanoScroller({scrollTop: (topScrollPosition - (scrollerHeight / 2))});
  };
  // scroller
  setTimeout(function(){

    $("#nano-scoller-1").nanoScroller();
    
  }, 0);


  // generate List

    $scope.scoreTable = [];

    
      

    scoreService.getPlayers().then(function(data){
        $scope.scoreTable = data.data;

        $scope.liveScoreTable = angular.copy($scope.scoreTable);
    });

  // set List
      
      $scope.getPlayersfromDB = function(){
        scoreService.getPlayers().then(function(data){
            $scope.scoreTable = data.data;

            $scope.liveScoreTable = angular.copy($scope.scoreTable);
        });
      };
      
      $scope.idExists = function (id){
        for(var i=0; i < $scope.liveScoreTable.length; i++){
          if($scope.liveScoreTable[i].id == id){
            return {
              i: i,
              value: true
            };
          }
        }
        return false;
      };
      $scope.idExistsDB = function (id){
        for(var i=0; i < $scope.scoreTable.length; i++){
          if($scope.scoreTable[i].id == id){
            return {
              i: i,
              value: true
            };
          }
        }
        return false;
      };

      $scope.updatePlayer = function(score) {
        

        $("#nano-scoller-1").nanoScroller();
        

        $scope.player.score = score;
        $scope.$apply();
        //console.log($scope.idExists($scope.player.id).value);
        //console.log($scope.idExists($scope.player.id).i);
        //console.log($scope.liveScoreTable);

        if($scope.idExists($scope.player.id).value){ 
          // wenn Spieler schon existiert


          if($scope.liveScoreTable[$scope.idExists($scope.player.id).i].score < $scope.player.score){
            // nur wenn neuer score größer ist als alter
            $scope.liveScoreTable[$scope.idExists($scope.player.id).i].score = angular.copy($scope.player.score);
            $scope.$apply();
            $scope.scrollLiveTable();
          } 

        } else {
          // wenn Spieler noch nicht existiert
          if(typeof $scope.liveScoreTable == "string"){
            $scope.liveScoreTable = [];
          }
          $scope.liveScoreTable.push(angular.copy($scope.player));
          $scope.$apply();
        }

        
      };

      $scope.savePlayer = function() {
        $scope.player.date = new Date();
        
        console.log($scope.player);
        

        if($scope.idExistsDB($scope.player.id).value){ 
          // wenn Spieler schon existiert
          //console.log('ist schon drin');
          if($scope.scoreTable[$scope.idExists($scope.player.id).i].score < $scope.player.score){
            //console.log('jetzt ist größer');
            // nur wenn neuer score größer ist als alter
            $scope.scoreTable[$scope.idExistsDB($scope.player.id).i].score = angular.copy($scope.player.score);
            $scope.$apply();
            
            scoreService.updatePlayer($scope.player.id, $scope.player);
          }

        } else {
          // wenn Spieler noch nicht existiert

          $scope.scoreTable.push(angular.copy($scope.player));
          $scope.$apply();
          //console.log('instert'); 
          scoreService.insertPlayer($scope.player);
        }
      }

});


/*app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
      when('/', {
        title: 'Game',
        //templateUrl: 'partials/customers.html',
        controller: 'listCtrl'
      })
      .otherwise({
        redirectTo: '/'
      });

}]);*/
/*app.run(['$location', '$rootScope', function($location, $rootScope) {
    $rootScope.$on('$routeChangeSuccess', function (event, current, previous) {
        $rootScope.title = current.$$route.title;
    });
}]);*/
