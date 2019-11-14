import React from 'react';
import Web3 from 'web3';
import { KICKSTARTER_ABI, KICKSTARTER_ADDRESS } from '../../config'

import './projects.css';
import Paper from '@material-ui/core/Paper';
import Typography from '@material-ui/core/Typography';
import Button from '@material-ui/core/Button';
import Toolbar from '../../components/toolbar/toolbar';
import DonateDialog from '../../components/modal/modal';

import List from '@material-ui/core/List';
import ListItem from '@material-ui/core/ListItem';
import ListItemText from '@material-ui/core/ListItemText';
import ListItemAvatar from '@material-ui/core/ListItemAvatar';
import Avatar from '@material-ui/core/Avatar';
import MonetizationOnIcon from '@material-ui/icons/MonetizationOn';
import EventIcon from '@material-ui/icons/Event';

class Projects extends React.Component{

	constructor(props) {
		super(props)
		this.state = {
		  projects : [],
		};
	}

	componentDidMount(){
		this.loadBlockchainData();
	}

	async loadBlockchainData() {
		await window.ethereum.enable();
		const web3 = new Web3(Web3.givenProvider || "http://localhost:8545")
		const accounts = await web3.eth.getAccounts()
		this.setState({ account: accounts[0] })
		const kickstarter = new web3.eth.Contract(KICKSTARTER_ABI, KICKSTARTER_ADDRESS);
		console.log(kickstarter.methods);
		let count = await kickstarter.methods.getProyectosCount().call()
		this.contribute = kickstarter.methods.contributeProyecto;
		let projects = [];
		for (var i = 0; i < count; i++) {
			let p = await kickstarter.methods.proyectos(i).call();
			let verify = await kickstarter.methods.verifyProyecto(i);
			p = { ...p, verify: verify };
			// console.log(p);
			// console.log( new Date(p.endDate*1000));
			// console.log( new Date(p.creationDate*1000));
			projects.push(p);
		}
		this.setState({ projects: projects });
	}
	
	render(){

		function Project(props){
			const [open, setOpen] = React.useState(false);

			const handleClickOpen = () => {
				setOpen(true);
			};

			const handleClose = () => {
				setOpen(false);
			};

			const getEstado = () => {
				if(props.estado === "1")
					return "Abierto";
				if(props.estado === "0")
					return "Cerrado";
				return "Cancelado";
			}

		    var dateOptions = { day: 'numeric', month: 'numeric', year: 'numeric' }
	

			return (
				<Paper className="project" elevation={5}>
					<DonateDialog id={ props.id } contribute={ props.contribute } account={props.account} open={open} handleClose={handleClose.bind(this)}/>
					<Typography className="info" variant="h6">
						{ props.name }
					</Typography>
					<Typography className="info" variant="subtitle1">
						{ 'Proyecto #' + props.id + ' - ' + getEstado() }
					</Typography>

					<List className="root">
				      <ListItem>
				        <ListItemAvatar>
				          <Avatar>
				            <MonetizationOnIcon />
				          </Avatar>
				        </ListItemAvatar>
				        <ListItemText primary={props.currentAmount/10**18 + ' eth'} secondary={"De " + props.amount/10**18  + ' eth'} />
				      </ListItem>
				      <ListItem>
				        <ListItemAvatar>
				          <Avatar>
				            <EventIcon />
				          </Avatar>
				        </ListItemAvatar>
				        {/*<ListItemText primary={'Quedan ' + props.days + ' días'} secondary={'De un total de ' + props.days } />*/}
				        <ListItemText 
				        	primary={'Proyecto termina el ' + props.endDate.toLocaleDateString([], dateOptions)} 
				        	secondary={'Fue creado el ' + props.creationDate.toLocaleDateString([], dateOptions) } />
				      </ListItem>
				    </List>
				    <div className="project-buttons">
					    <Button variant="outlined" color="primary" onClick={ handleClickOpen }>
					    	Aportar
					    </Button>
					    <Button variant="outlined" color="secondary" onClick={ () => { 
					    	props.verify.send({ from: props.account }).then((algo) => {
					    		console.log(algo);
					    	})
					    } }>
					    	Verificar
					    </Button>
				    </div>
				</Paper>
			);
		}

		return (
			<div>
				<Toolbar/>
				<Typography className="title" variant="h4">
					Proyectos
				</Typography>
				<div className="projects-container">
				{ 	
					this.state.projects.map((p, i) => 
						<Project 
							key={i} id={i} 
							name={ p.name } amount={ p.amount }
							endDate={ new Date(p.endDate*1000) } 
							creationDate={ new Date(p.creationDate*1000) } 
							currentAmount={p.currentAmount} estado={p.estado}
							contribute={ this.contribute }
							account={this.state.account}
							verify={p.verify}
						/>
					)
				}
				</div>
			</div>
		);
	}
}

export default Projects;