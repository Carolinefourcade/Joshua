document.addEventListener("DOMContentLoaded", () => {
    $('#modal-success').modal('hide');
    $('#modal-endtime').modal('hide');


    const flagSolutionForm = document.getElementById('form-flag');
    flagSolutionForm.addEventListener('submit', (event)=>{
            event.preventDefault();
            let challenge_id         = document.getElementById('challenge-id').value;
            let challenge_order      = document.getElementById('challenge-order').value;
            let contest_id           = document.getElementById('contest-id').value;
            let flagSolution         = document.getElementById('flag').value;
            let solutionForm         = document.getElementById('solution-form');
            const errorMessage       = document.getElementById('error-message');
            const spinner            = document.getElementById('spinner-solution');
            const successMessageText = '<img src="/assets/images/white-right-flag.svg" width="40"> That\'s it. <a class="btn btn-dark text-white" href="/contest/play/' + contest_id + '">> Next step</a>';
            const errorMessageText   = 'Certainly not.';

            spinner.classList.remove('hide');

        let params = {
            'challenge_id'    : challenge_id,
            'challenge_order' : challenge_order,
            'contest_id'      : contest_id,
            'flagSolution'    : flagSolution
        };

        let url ='/contest/sendSolution';

        const request = new Request(url, {
            method: 'POST',
            headers: {
                'Accept'      : 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(params),
        });
        fetch(request).then(response =>response.json())
        .then( data => {
            if (data.message === 'error') {
                errorMessage.classList.remove('alert-success');
                errorMessage.classList.add('alert', 'alert-danger');
                errorMessage.innerHTML = errorMessageText;
            } else if (data.message === 'success') {
                errorMessage.classList.remove('alert-danger');
                errorMessage.classList.add('alert', 'alert-success');
                solutionForm.classList.add('hide');
                errorMessage.innerHTML = successMessageText;
            } else if (data.message === 'end') {
                errorMessage.classList.remove('alert-danger');
                errorMessage.classList.add('alert', 'alert-success');
                solutionForm.classList.add('hide');
                errorMessage.innerHTML = successMessageText;
                $('#modal-success').modal('show');

            }
            spinner.classList.add('hide');
        });
    });
});
