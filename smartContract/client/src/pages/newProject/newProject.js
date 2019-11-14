import React from 'react';
import Web3 from 'web3';
import { KICKSTARTER_ABI, KICKSTARTER_ADDRESS } from '../../config';

import './newProject.css';
import Toolbar from '../../components/toolbar/toolbar';
import Paper from '@material-ui/core/Paper';
import Typography from '@material-ui/core/Typography';
import Input from '@material-ui/core/Input';
import Button from '@material-ui/core/Button';


class NewProject extends React.Component{

	constructor(props) {
		super(props)
		this.state = {
		  name : '',
		  amount: null,
		  days: null,
		};
		this.createProject = () => {};
	}

	componentDidMount() {
		this.loadBlockchainData();
	}


	async loadBlockchainData() {
		await window.ethereum.enable();
		const web3 = new Web3(Web3.givenProvider || "http://localhost:8545")
		const accounts = await web3.eth.getAccounts()
		this.setState({ account: accounts[0] })
		const kickstarter = new web3.eth.Contract(KICKSTARTER_ABI, KICKSTARTER_ADDRESS)
		this.setState({ kickstarter })
		console.log(kickstarter.methods);
		this.createProject = kickstarter.methods.createProyecto;

	}
	
	handleInputChange = (event) => {
	    const { value, name } = event.target;
	    this.setState({
	      [name]: value
	    });
  	}

  	create(){
  		console.log(this.state);
  		this.createProject(this.state.name, (this.state.amount*10**18).toString(), this.state.days).send({ from: this.state.account })
  	}

	render(){
		return (
			<div className="container">
			<Toolbar/>
				<Typography className="title" variant="h4">
					Crear nuevo proyecto
				</Typography>
				<Paper className="account-paper" elevation={5}>
					<Typography variant="h6" >
						Tu cuenta
					</Typography>
					<Typography>
						{ this.state.account }
					</Typography>
				</Paper>
				<Paper className="paper" elevation={5}>
		            
					<Input
					  id="name"
		              name="name"
		              fullWidth
		              className="input"
		              placeholder="Nombre del proyecto"
		              onChange={this.handleInputChange}
		              autoFocus
		            />
					<Input
					  id="amount"
		              name="amount"
		              fullWidth
		              type="number"
		              className="input"
		              placeholder="Ether necesario para el proyecto"
		              onChange={this.handleInputChange}
		            />
					<Input
					  id="days"
		              name="days"
		              fullWidth
		              className="input"
		              placeholder="Duración en días del proyecto"
		              onChange={this.handleInputChange}
		            />

		            <Button variant="contained" color="primary" onClick={ this.create.bind(this) }>
		            	Crear
					</Button>
				</Paper>
			</div>
		);
	}
}

export default NewProject;