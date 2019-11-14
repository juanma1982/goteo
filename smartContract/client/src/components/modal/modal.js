import React from 'react';
import Web3 from 'web3';
import Button from '@material-ui/core/Button';
import TextField from '@material-ui/core/TextField';
import Dialog from '@material-ui/core/Dialog';
import DialogActions from '@material-ui/core/DialogActions';
import DialogContent from '@material-ui/core/DialogContent';
import DialogContentText from '@material-ui/core/DialogContentText';
import DialogTitle from '@material-ui/core/DialogTitle';

export default function DonateDialog(props) {

  const [amount, setAmount] = React.useState(0);

  const contribute = () => {
    console.log({ id: props.id, amount: amount*10**18 });
    props.contribute((props.id).toString(), (amount*10**18).toString()).send({ from: props.account, value: (amount*10**18).toString() })
    props.handleClose();
  };

  return (
    <div>
      <Dialog open={props.open} onClose={props.handleClose} aria-labelledby="form-dialog-title">
        <DialogTitle id="form-dialog-title">Aportar al proyecto</DialogTitle>
        <DialogContent>
          <DialogContentText>
            Podés aportar al proyecto la cantidad de ether que desees. Si el proyecto no llega a
            recaudar el dinero necesitado se te devolverá a tu cuenta.
          </DialogContentText>
          <TextField
            autoFocus
            margin="dense"
            id="amount"
            label="Etherium a aportar"
            onChange={e => setAmount(e.target.value)}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={props.handleClose} color="primary">
            Cancelar
          </Button>
          <Button onClick={contribute} color="primary">
            Aportar
          </Button>
        </DialogActions>
      </Dialog>
    </div>
  );
}