angular.module('uImageUpload', []).directive('uImageUpload', function () {
    'use strict';
    return {
        restrict: 'E',
        transclude: false,
        controller: function ($scope, $element, $attrs) {
            $scope.showImage = function (input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        $scope.$apply(function () {
                            $scope[$attrs.uFileName] = e.timeStamp;
                            $scope[$attrs.uFileSrc] = e.target.result;
                        });
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            };
            $scope.uploadFile = function () {
                $element.find('[type=file]').click();
            };
            $scope.removePic = function () {
                //Clear the file input so the onchange event is still
                //triggered when uploading same image twice in a row.
                var clearInput = function (source) {
                    var $form = angular.element('<form>'),
                        $targ = source.clone().appendTo($form);
                    $form[0].reset();
                    source.replaceWith($targ);
                };
                clearInput($element.find('[type=file]'));
                $scope[$attrs.uFileSrc] = $scope[$attrs.uFileDefaultSrc];
                $scope[$attrs.uFileName] = '';
            };
        },
        compile: function (element, attrs, transclude) {
            element.find('[type=file]').attr('onchange', 'angular.element(this).scope().showImage(this)');
        }

    };

});


